<?php
/**
 * Revision Management
 *
 * @package TestMethodWorkflow
 */

// Exit if accessed directly
if (!defined('ABSPATH')) exit;

/**
 * Test Method revision manager class
 */
class TestMethod_RevisionManager {
	
	/**
	 * Constructor
	 */
	public function __construct() {
		// Add meta box for revision management
		add_action('add_meta_boxes', array($this, 'add_revision_meta_box'));
		
	// AJAX handlers for revision creation and publishing
	add_action('wp_ajax_create_test_method_revision', array($this, 'create_revision'));
	add_action('wp_ajax_publish_test_method_revision', array($this, 'publish_revision'));
	add_action('wp_ajax_publish_approved_revision', array($this, 'publish_approved_revision'));
		
		// Add filter to admin list to show revisions
		add_filter('parse_query', array($this, 'filter_admin_list_by_revision'));
		
		// Add filter dropdown to admin
		add_action('restrict_manage_posts', array($this, 'add_revision_filter_dropdown'));
	}
	
	/**
	 * Add revision meta box
	 */
	public function add_revision_meta_box() {
		// Only add for published and locked posts
		$screen = get_current_screen();
		if ($screen->base != 'post' || $screen->post_type != 'test_method') {
			return;
		}
		
		global $post;
		
		// Skip this meta box for revisions
		if (get_post_meta($post->ID, '_is_revision', true)) {
			return;
		}
		
		// Add meta box
		add_meta_box(
			'test_method_revision_manager',
			__('Revision Management', 'test-method-workflow'),
			array($this, 'revision_meta_box_callback'),
			'test_method',
			'side',
			'default'
		);
	}
	
	/**
	 * Revision meta box callback
	 */
public function revision_meta_box_callback($post) {
		 wp_nonce_field('test_method_revision_nonce', 'test_method_revision_nonce');
		 
		 // Check if post has existing revisions
		 $revisions = $this->get_post_revisions($post->ID);
		 $has_revisions = !empty($revisions);
		 
		 // Check if post is locked
		 $is_locked = get_post_meta($post->ID, '_is_locked', true);
		 
		 // Check user roles
		 $user = wp_get_current_user();
		 $user_roles = (array) $user->roles;
		 $can_create_revision = array_intersect($user_roles, array('tp_contributor', 'tp_approver', 'tp_admin', 'administrator'));
		 
		 echo '<div class="revision-manager-container">';
		 
		 if ($has_revisions) {
			 echo '<div class="notice notice-warning" style="margin: 5px 0;">';
			 echo '<p>' . __('This test method already has an active revision. You must complete or delete the existing revision before creating a new one.', 'test-method-workflow') . '</p>';
			 echo '</div>';
			 
			 echo '<h4>' . __('Active Revisions', 'test-method-workflow') . '</h4>';
			 echo '<ul class="revision-list">';
			 
			 foreach ($revisions as $revision) {
				 $workflow_status = get_post_meta($revision->ID, '_workflow_status', true);
				 $status_display = ucfirst(str_replace('_', ' ', $workflow_status ?: 'draft'));
				 
				 echo '<li>';
				 echo '<a href="' . get_edit_post_link($revision->ID) . '">' . esc_html($revision->post_title) . '</a>';
				 echo ' - <span class="revision-status">' . esc_html($status_display) . '</span>';
				 echo '</li>';
			 }
			 
			 echo '</ul>';
		 }
		 
		 // Show create revision button for locked published posts
		 if ($post->post_status === 'publish' || $is_locked) {
			 if ($can_create_revision) {
				 if ($is_locked && !array_intersect($user_roles, array('administrator', 'tp_admin'))) {
					 echo '<p>' . __('Only administrators can create revisions of locked test methods.', 'test-method-workflow') . '</p>';
				 } else {
					 if (!$has_revisions) {
						 echo '<p>' . __('Create a new revision to propose changes to this test method.', 'test-method-workflow') . '</p>';
						 
						 // Include nonce directly in the data attribute
						 $nonce = wp_create_nonce('test_method_revision_nonce');
						 echo '<button type="button" class="button create-revision" data-post-id="' . $post->ID . '" data-nonce="' . $nonce . '">' . 
							  __('Create New Revision', 'test-method-workflow') . '</button>';
					 }
				 }
			 }
		 } else {
			 echo '<p>' . __('You can create revisions once this test method is published.', 'test-method-workflow') . '</p>';
		 }
		 
		 echo '</div>';
	 }
	 	
	/**
	 * Get post revisions
	 */
	private function get_post_revisions($post_id) {
		$args = array(
			'post_type' => 'test_method',
			'post_status' => array('draft', 'pending', 'private'),
			'posts_per_page' => -1,
			'meta_query' => array(
				array(
					'key' => '_revision_parent',
					'value' => $post_id,
					'compare' => '='
				),
				array(
					'key' => '_is_revision',
					'value' => '1',
					'compare' => '='
				)
			)
		);
		
		return get_posts($args);
	}
	
	/**
	 * AJAX handler for creating revisions
	 */
public function create_revision() {
		 // Check if there's a nonce in the request
		 if (!isset($_POST['nonce'])) {
			 wp_send_json_error('Security nonce is missing');
			 return;
		 }
		 
		 // Check nonce
		 check_ajax_referer('test_method_revision_nonce', 'nonce');
		 
		 // Check permissions
		 if (!current_user_can('edit_test_methods')) {
			 wp_send_json_error('Permission denied: You do not have permission to create revisions.');
			 return;
		 }
		 
		 $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
		 $version_type = isset($_POST['version_type']) ? sanitize_text_field($_POST['version_type']) : 'minor';
		 
		 if (!$post_id) {
			 wp_send_json_error('Invalid post ID: The post ID provided is invalid.');
			 return;
		 }
		 
		 $post = get_post($post_id);
		 
		 if (!$post || $post->post_type !== 'test_method') {
			 wp_send_json_error('Invalid test method: The requested post does not exist or is not a test method.');
			 return;
		 }
		 
		 // Check if a revision already exists for this post
		 $existing_revisions = $this->get_post_revisions($post_id);
		 
		 if (!empty($existing_revisions)) {
			 wp_send_json_error('A revision for this test method already exists. Please complete or delete the existing revision before creating a new one.');
			 return;
		 }
		 
		 // Get current version information for the revision
		 $current_version = get_post_meta($post_id, '_current_version_number', true);
		 if (empty($current_version)) {
			 $current_version = '0.0';
		 }
		 
		 // Calculate next version based on version_type
		 $version_parts = explode('.', $current_version);
		 $major = isset($version_parts[0]) ? intval($version_parts[0]) : 0;
		 $minor = isset($version_parts[1]) ? intval($version_parts[1]) : 0;
		 
		 if ($version_type === 'minor') {
			 $next_version = $major . '.' . ($minor + 1);
		 } else if ($version_type === 'major') {
			 $next_version = ($major + 1) . '.0';
		 } else {
			 $next_version = $current_version; // Default - no change
		 }
		 
		 $revision_title = $post->post_title . ' - ' . __('Revision', 'test-method-workflow');
		 
		 // Create new revision post with additional logging
		 try {
			 // Get the latest content from the editor
			 $post_content = isset($_POST['post_content']) ? $_POST['post_content'] : $post->post_content;
			 $post_title = isset($_POST['post_title']) ? $_POST['post_title'] : $post->post_title;
			 
			 $revision_args = array(
				 'post_title' => $revision_title,
				 'post_content' => $post_content,
				 'post_excerpt' => $post->post_excerpt,
				 'post_type' => 'test_method',
				 'post_status' => 'draft',
				 'post_author' => get_current_user_id(),
				 'comment_status' => $post->comment_status,
				 'ping_status' => $post->ping_status,
				 'meta_input' => array(
					 '_is_revision' => '1',
					 '_revision_parent' => $post_id,
					 '_workflow_status' => 'draft',
					 '_current_version_number' => $next_version, // Set the incremented version
					 '_revision_version' => $current_version,
					 '_approvals' => array(),
					 '_cpt_version' => $version_type,
					 '_version_already_incremented' => 'yes' // Add this flag
				 )
			 );
			 
			 $revision_id = wp_insert_post($revision_args);
			 
			 if (is_wp_error($revision_id)) {
				 wp_send_json_error('Failed to create revision: ' . $revision_id->get_error_message());
				 return;
			 }
			 
			 // Copy taxonomies
			 $taxonomies = get_object_taxonomies($post->post_type);
			 foreach ($taxonomies as $taxonomy) {
				 $terms = wp_get_object_terms($post_id, $taxonomy, array('fields' => 'ids'));
				 if (!is_wp_error($terms)) {
					 wp_set_object_terms($revision_id, $terms, $taxonomy);
				 }
			 }
			 
			 // Add record to revision history of parent
			 $revision_history = get_post_meta($post_id, '_revision_history', true);
			 if (!is_array($revision_history)) {
				 $revision_history = array();
			 }
			 
			 $revision_history[] = array(
				 'version' => count($revision_history) + 1,
				 'user_id' => get_current_user_id(),
				 'date' => time(),
				 'status' => 'revision created for ' . $version_type . ' version change',
				 'revision_id' => $revision_id,
				 'version_number' => $current_version,
				 'next_version' => $next_version
			 );
			 
			 update_post_meta($post_id, '_revision_history', $revision_history);
			 
			 // Send notification
			 do_action('tmw_send_notification', $revision_id, 'revision_created');
			 
			 // Success!
			 wp_send_json_success(array(
				 'message' => __('Revision created successfully', 'test-method-workflow'),
				 'revision_id' => $revision_id,
				 'edit_url' => get_edit_post_link($revision_id, 'raw')
			 ));
			 
		 } catch (Exception $e) {
			 wp_send_json_error('Error creating revision: ' . $e->getMessage());
		 }
	 }
	
	/**
	 * AJAX handler for publishing revisions
	 */
public function publish_revision() {
		 // Check nonce
		 check_ajax_referer('test_method_revision_nonce', 'nonce');
		 
		 // Check permissions
		 if (!current_user_can('publish_test_methods')) {
			 wp_send_json_error('Permission denied');
			 return;
		 }
		 
		 $revision_id = isset($_POST['revision_id']) ? intval($_POST['revision_id']) : 0;
		 $parent_id = isset($_POST['parent_id']) ? intval($_POST['parent_id']) : 0;
		 
		 if (!$revision_id || !$parent_id) {
			 wp_send_json_error('Invalid post IDs');
			 return;
		 }
		 
		 $revision = get_post($revision_id);
		 $parent = get_post($parent_id);
		 
		 if (!$revision || !$parent || $revision->post_type !== 'test_method' || $parent->post_type !== 'test_method') {
			 wp_send_json_error('Invalid test methods');
			 return;
		 }
		 
		 // Verify this is actually a revision of the parent
		 $revision_parent = get_post_meta($revision_id, '_revision_parent', true);
		 if ($revision_parent != $parent_id) {
			 wp_send_json_error('Revision does not match parent');
			 return;
		 }
		 
		 // Check workflow status
		 $workflow_status = get_post_meta($revision_id, '_workflow_status', true);
		 
		 if ($workflow_status !== 'approved') {
			 wp_send_json_error('This revision must be approved before publishing');
			 return;
		 }
		 
		 // Get current version information
		 $parent_version = get_post_meta($parent_id, '_current_version_number', true);
		 $revision_version = get_post_meta($revision_id, '_current_version_number', true);
		 $version_note = get_post_meta($revision_id, '_cpt_version_note', true);
		 
		 if (empty($parent_version)) {
			 $parent_version = '0.1';
		 }
		 
		 // Update parent with revision content, but KEEP original post_name (slug/permalink)
		 $parent_slug = $parent->post_name;
		 
		 $update_args = array(
			 'ID' => $parent_id,
			 'post_title' => $revision->post_title,
			 'post_content' => $revision->post_content,
			 'post_excerpt' => $revision->post_excerpt,
			 'post_name' => $parent_slug // Ensure we keep the original URL/slug
		 );
		 
		 $result = wp_update_post($update_args);
		 
		 if (is_wp_error($result)) {
			 wp_send_json_error('Error updating parent: ' . $result->get_error_message());
			 return;
		 }
		 
		 // Copy taxonomies
		 $taxonomies = get_object_taxonomies($revision->post_type);
		 foreach ($taxonomies as $taxonomy) {
			 $terms = wp_get_object_terms($revision_id, $taxonomy, array('fields' => 'ids'));
			 if (!is_wp_error($terms)) {
				 wp_set_object_terms($parent_id, $terms, $taxonomy);
			 }
		 }
		 
		 // Get approval history from revision
		 $revision_approvals = get_post_meta($revision_id, '_approvals', true);
		 if (is_array($revision_approvals)) {
			 // Merge with parent approvals to maintain history
			 $parent_approvals = get_post_meta($parent_id, '_approvals', true);
			 if (!is_array($parent_approvals)) {
				 $parent_approvals = array();
			 }
			 
			 // Add a separator to indicate these are from a revision
			 $parent_approvals[] = array(
				 'user_id' => get_current_user_id(),
				 'date' => time(),
				 'status' => 'revision_separator',
				 'comment' => sprintf(__('Revision %s published', 'test-method-workflow'), $revision_version),
				 'version' => $revision_version
			 );
			 
			 // Add all revision approvals to the parent
			 foreach ($revision_approvals as $approval) {
				 $parent_approvals[] = $approval;
			 }
			 
			 // Update parent approvals
			 update_post_meta($parent_id, '_approvals', $parent_approvals);
		 }
		 
		 // Copy relevant meta fields (exclude workflow meta)
		 $exclude_meta_keys = array(
			 '_edit_lock', '_edit_last', '_wp_old_slug', '_wp_old_date',
			 '_workflow_status', '_is_locked', '_approvals', '_revision_parent',
			 '_is_revision', '_awaiting_final_approval', '_revision_history',
			 '_version_already_incremented' // Don't copy this flag
		 );
		 
		 $meta_keys = get_post_custom_keys($revision_id);
		 if ($meta_keys) {
			 foreach ($meta_keys as $meta_key) {
				 if (!in_array($meta_key, $exclude_meta_keys) && !wp_is_protected_meta($meta_key)) {
					 $meta_values = get_post_meta($revision_id, $meta_key, true);
					 update_post_meta($parent_id, $meta_key, $meta_values);
				 }
			 }
		 }
		 
		 // Update version if changed
		 if (!empty($revision_version) && $revision_version !== $parent_version) {
			 update_post_meta($parent_id, '_current_version_number', $revision_version);
		 }
		 
		 // Copy version note
		 if (!empty($version_note)) {
			 update_post_meta($parent_id, '_cpt_version_note', $version_note);
		 }
		 
		 // Lock the parent
		 update_post_meta($parent_id, '_is_locked', true);
		 update_post_meta($parent_id, '_workflow_status', 'publish');
		 
		 // Make sure parent is published
		 if ($parent->post_status !== 'publish') {
			 wp_update_post(array(
				 'ID' => $parent_id,
				 'post_status' => 'publish'
			 ));
		 }
		 
		 // Also merge revision history
		 $revision_history = get_post_meta($revision_id, '_revision_history', true);
		 if (is_array($revision_history)) {
			 $parent_history = get_post_meta($parent_id, '_revision_history', true);
			 if (!is_array($parent_history)) {
				 $parent_history = array();
			 }
			 
			 // Add a separator entry
			 $parent_history[] = array(
				 'version' => count($parent_history) + 1,
				 'user_id' => get_current_user_id(),
				 'date' => time(),
				 'status' => 'revision_published',
				 'revision_id' => $revision_id,
				 'version_number' => $revision_version
			 );
			 
			 // Add all revision history to parent
			 foreach ($revision_history as $history_item) {
				 $history_item['from_revision'] = true; // Mark as coming from revision
				 $parent_history[] = $history_item;
			 }
			 
			 update_post_meta($parent_id, '_revision_history', $parent_history);
		 }
		 
		 // Trash the revision or change status to show it's been applied
		 wp_update_post(array(
			 'ID' => $revision_id,
			 'post_status' => 'trash'
		 ));
		 
		 // Send notification
		 do_action('tmw_send_notification', $parent_id, 'revision_published');
		 
		 wp_send_json_success(array(
			 'message' => __('Revision published successfully', 'test-method-workflow'),
			 'parent_id' => $parent_id,
			 'edit_url' => get_edit_post_link($parent_id, 'raw')
		 ));
	 }
	 
	 /**
	  * AJAX handler for publishing approved revisions
	  */
	 public function publish_approved_revision() {
		 // Check nonce
		 check_ajax_referer('test_method_workflow', 'nonce');
		 
		 // Check permissions
		 if (!current_user_can('publish_test_methods')) {
			 wp_send_json_error('Permission denied');
			 return;
		 }
		 
		 $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
		 
		 if (!$post_id) {
			 wp_send_json_error('Invalid post ID');
			 return;
		 }
		 
		 // Verify this is a revision
		 $is_revision = get_post_meta($post_id, '_is_revision', true);
		 if (!$is_revision) {
			 wp_send_json_error('This is not a revision post');
			 return;
		 }
		 
		 // Get parent post ID
		 $parent_id = get_post_meta($post_id, '_revision_parent', true);
		 if (!$parent_id) {
			 wp_send_json_error('Parent post not found');
			 return;
		 }
		 
		 // Verify the revision is approved
		 $workflow_status = get_post_meta($post_id, '_workflow_status', true);
		 if ($workflow_status !== 'approved') {
			 wp_send_json_error('This revision must be approved before publishing');
			 return;
		 }
		 
		 // Use the existing publish_revision method
		 $this->publish_revision_to_parent($post_id, $parent_id);
		 
		 wp_send_json_success(array(
			 'message' => __('Revision published successfully', 'test-method-workflow'),
			 'parent_id' => $parent_id,
			 'view_url' => get_permalink($parent_id)
		 ));
	 }
	 
	 /**
	  * Helper function to publish a revision to its parent
	  */
	 private function publish_revision_to_parent($revision_id, $parent_id) {
		 $revision = get_post($revision_id);
		 $parent = get_post($parent_id);
		 
		 if (!$revision || !$parent) {
			 return false;
		 }
		 
		 // Get current version information
		 $parent_version = get_post_meta($parent_id, '_current_version_number', true);
		 $revision_version = get_post_meta($revision_id, '_current_version_number', true);
		 $version_note = get_post_meta($revision_id, '_cpt_version_note', true);
		 
		 if (empty($parent_version)) {
			 $parent_version = '0.1';
		 }
		 
		 // Update parent with revision content, but KEEP original post_name (slug/permalink)
		 $parent_slug = $parent->post_name;
		 
		 $update_args = array(
			 'ID' => $parent_id,
			 'post_title' => $revision->post_title,
			 'post_content' => $revision->post_content,
			 'post_excerpt' => $revision->post_excerpt,
			 'post_name' => $parent_slug // Ensure we keep the original URL/slug
		 );
		 
		 $result = wp_update_post($update_args);
		 
		 if (is_wp_error($result)) {
			 return false;
		 }
		 
		 // Copy taxonomies
		 $taxonomies = get_object_taxonomies($revision->post_type);
		 foreach ($taxonomies as $taxonomy) {
			 $terms = wp_get_object_terms($revision_id, $taxonomy, array('fields' => 'ids'));
			 if (!is_wp_error($terms)) {
				 wp_set_object_terms($parent_id, $terms, $taxonomy);
			 }
		 }
		 
		 // Get approval history from revision
		 $revision_approvals = get_post_meta($revision_id, '_approvals', true);
		 if (is_array($revision_approvals)) {
			 // Merge with parent approvals to maintain history
			 $parent_approvals = get_post_meta($parent_id, '_approvals', true);
			 if (!is_array($parent_approvals)) {
				 $parent_approvals = array();
			 }
			 
			 // Add a separator to indicate these are from a revision
			 $parent_approvals[] = array(
				 'user_id' => get_current_user_id(),
				 'date' => time(),
				 'status' => 'revision_separator',
				 'comment' => sprintf(__('Revision %s published', 'test-method-workflow'), $revision_version),
				 'version' => $revision_version
			 );
			 
			 // Add all revision approvals to the parent
			 foreach ($revision_approvals as $approval) {
				 $parent_approvals[] = $approval;
			 }
			 
			 // Update parent approvals
			 update_post_meta($parent_id, '_approvals', $parent_approvals);
		 }
		 
		 // Copy relevant meta fields (exclude workflow meta)
		 $exclude_meta_keys = array(
			 '_edit_lock', '_edit_last', '_wp_old_slug', '_wp_old_date',
			 '_workflow_status', '_is_locked', '_approvals', '_revision_parent',
			 '_is_revision', '_awaiting_final_approval', '_revision_history',
			 '_version_already_incremented' // Don't copy this flag
		 );
		 
		 $meta_keys = get_post_custom_keys($revision_id);
		 if ($meta_keys) {
			 foreach ($meta_keys as $meta_key) {
				 if (!in_array($meta_key, $exclude_meta_keys) && !wp_is_protected_meta($meta_key)) {
					 $meta_values = get_post_meta($revision_id, $meta_key, true);
					 update_post_meta($parent_id, $meta_key, $meta_values);
				 }
			 }
		 }
		 
		 // Update version if changed
		 if (!empty($revision_version) && $revision_version !== $parent_version) {
			 update_post_meta($parent_id, '_current_version_number', $revision_version);
		 }
		 
		 // Copy version note
		 if (!empty($version_note)) {
			 update_post_meta($parent_id, '_cpt_version_note', $version_note);
		 }
		 
		 // Lock the parent
		 update_post_meta($parent_id, '_is_locked', true);
		 update_post_meta($parent_id, '_workflow_status', 'publish');
		 
		 // Make sure parent is published
		 if ($parent->post_status !== 'publish') {
			 wp_update_post(array(
				 'ID' => $parent_id,
				 'post_status' => 'publish'
			 ));
		 }
		 
		 // Also merge revision history
		 $revision_history = get_post_meta($revision_id, '_revision_history', true);
		 if (is_array($revision_history)) {
			 $parent_history = get_post_meta($parent_id, '_revision_history', true);
			 if (!is_array($parent_history)) {
				 $parent_history = array();
			 }
			 
			 // Add a separator entry
			 $parent_history[] = array(
				 'version' => count($parent_history) + 1,
				 'user_id' => get_current_user_id(),
				 'date' => time(),
				 'status' => 'revision_published',
				 'revision_id' => $revision_id,
				 'version_number' => $revision_version
			 );
			 
			 // Add all revision history to parent
			 foreach ($revision_history as $history_item) {
				 $history_item['from_revision'] = true; // Mark as coming from revision
				 $parent_history[] = $history_item;
			 }
			 
			 update_post_meta($parent_id, '_revision_history', $parent_history);
		 }
		 
		 // Trash the revision
		 wp_update_post(array(
			 'ID' => $revision_id,
			 'post_status' => 'trash'
		 ));
		 
		 // Send notification
		 do_action('tmw_send_notification', $parent_id, 'revision_published');
		 
		 return true;
	 }
	
	/**
	 * Add revision filter dropdown to admin
	 */
	public function add_revision_filter_dropdown($post_type) {
		if ($post_type !== 'test_method') {
			return;
		}
		
		$revision_status = isset($_GET['revision_status']) ? $_GET['revision_status'] : '';
		
		?>
		<select name="revision_status">
			<option value=""><?php _e('All Test Methods', 'test-method-workflow'); ?></option>
			<option value="parent" <?php selected($revision_status, 'parent'); ?>><?php _e('Parent Posts Only', 'test-method-workflow'); ?></option>
			<option value="revision" <?php selected($revision_status, 'revision'); ?>><?php _e('Revisions Only', 'test-method-workflow'); ?></option>
		</select>
		<?php
	}
	
	/**
	 * Filter admin list by revision status
	 */
	public function filter_admin_list_by_revision($query) {
		global $pagenow, $post_type;
		
		// Only on test method list screen
		if (!is_admin() || $pagenow !== 'edit.php' || $post_type !== 'test_method' || !$query->is_main_query()) {
			return $query;
		}
		
		// Check if filter is active
		$revision_status = isset($_GET['revision_status']) ? $_GET['revision_status'] : '';
		
		if ($revision_status === 'parent') {
			// Show only parent posts
			$meta_query = $query->get('meta_query');
			if (!is_array($meta_query)) {
				$meta_query = array();
			}
			
			$meta_query[] = array(
				'relation' => 'OR',
				array(
					'key' => '_is_revision',
					'compare' => 'NOT EXISTS'
				),
				array(
					'key' => '_is_revision',
					'value' => '1',
					'compare' => '!='
				)
			);
			
			$query->set('meta_query', $meta_query);
		} elseif ($revision_status === 'revision') {
			// Show only revisions
			$meta_query = $query->get('meta_query');
			if (!is_array($meta_query)) {
				$meta_query = array();
			}
			
			$meta_query[] = array(
				'key' => '_is_revision',
				'value' => '1',
				'compare' => '='
			);
			
			$query->set('meta_query', $meta_query);
		}
		
		return $query;
	}
}
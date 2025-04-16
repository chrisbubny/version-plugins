<?php
/**
 * Handles workflow state management
 */
/**
 * Register custom post statuses for workflow states
 */
function tm_register_post_statuses() {
	register_post_status('submitted', array(
		'label' => _x('Submitted for Review', 'post status', 'test-method-versioning'),
		'public' => false,
		'exclude_from_search' => true,
		'show_in_admin_all_list' => true,
		'show_in_admin_status_list' => true,
		'label_count' => _n_noop('Submitted for Review <span class="count">(%s)</span>',
							   'Submitted for Review <span class="count">(%s)</span>'),
	));
	
	register_post_status('approved', array(
		'label' => _x('Approved', 'post status', 'test-method-versioning'),
		'public' => false,
		'exclude_from_search' => true,
		'show_in_admin_all_list' => true,
		'show_in_admin_status_list' => true,
		'label_count' => _n_noop('Approved <span class="count">(%s)</span>',
							   'Approved <span class="count">(%s)</span>'),
	));
	
	register_post_status('rejected', array(
		'label' => _x('Rejected', 'post status', 'test-method-versioning'),
		'public' => false,
		'exclude_from_search' => true,
		'show_in_admin_all_list' => true,
		'show_in_admin_status_list' => true,
		'label_count' => _n_noop('Rejected <span class="count">(%s)</span>',
							   'Rejected <span class="count">(%s)</span>'),
	));
	
	register_post_status('revision', array(
		'label' => _x('Revision', 'post status', 'test-method-versioning'),
		'public' => false,
		'exclude_from_search' => true,
		'show_in_admin_all_list' => true,
		'show_in_admin_status_list' => true,
		'label_count' => _n_noop('Revision <span class="count">(%s)</span>',
							   'Revision <span class="count">(%s)</span>'),
	));
}
add_action('init', 'tm_register_post_statuses');

/**
 * Add workflow status options to post submit box
 */
function tm_add_workflow_status_box() {
	global $post;
	
	// Only show for test_method post type
	if (!$post || get_post_type($post) !== 'test_method') {
		return;
	}
	
	// Get current status
	$current_status = $post->post_status;
	
	// Define available transitions based on current status
	$transitions = array();
	
	if ($current_status === 'draft') {
		$transitions['submitted'] = __('Submit for Review', 'test-method-versioning');
	} else if ($current_status === 'submitted') {
		if (current_user_can('manage_options')) {
			$transitions['approved'] = __('Approve', 'test-method-versioning');
			$transitions['rejected'] = __('Reject', 'test-method-versioning');
		}
		$transitions['draft'] = __('Back to Draft', 'test-method-versioning');
	} else if ($current_status === 'approved') {
		$transitions['publish'] = __('Publish', 'test-method-versioning');
		$transitions['draft'] = __('Back to Draft', 'test-method-versioning');
	} else if ($current_status === 'rejected') {
		$transitions['draft'] = __('Back to Draft', 'test-method-versioning');
		$transitions['submitted'] = __('Submit Again', 'test-method-versioning');
	} else if ($current_status === 'publish') {
		$transitions['revision'] = __('Create Revision', 'test-method-versioning');
	}
	
	if (empty($transitions)) {
		return;
	}
	
	// Output status dropdown
	?>
	<div class="misc-pub-section tm-workflow-status">
		<label><strong><?php _e('Workflow Status:', 'test-method-versioning'); ?></strong></label>
		<select name="tm_workflow_status" id="tm_workflow_status">
			<option value=""><?php _e('— No Change —', 'test-method-versioning'); ?></option>
			<?php foreach ($transitions as $status => $label) : ?>
				<option value="<?php echo esc_attr($status); ?>"><?php echo esc_html($label); ?></option>
			<?php endforeach; ?>
		</select>
	</div>
	<?php
}
add_action('post_submitbox_misc_actions', 'tm_add_workflow_status_box');

/**
 * Process workflow status transition on post save
 */
function tm_process_workflow_status($post_id, $post, $update) {
	// Skip autosaves and revisions
	if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
		return;
	}
	
	if (wp_is_post_revision($post_id)) {
		return;
	}
	
	// Check if we have a status transition
	if (!isset($_POST['tm_workflow_status']) || empty($_POST['tm_workflow_status'])) {
		return;
	}
	
	// Get new status
	$new_status = sanitize_text_field($_POST['tm_workflow_status']);
	
	// Update post status
	wp_update_post(array(
		'ID' => $post_id,
		'post_status' => $new_status
	));
	
	// Record the transition
	$transitions = get_post_meta($post_id, 'workflow_transitions', true);
	if (!is_array($transitions)) {
		$transitions = array();
	}
	
	$transitions[] = array(
		'from' => $post->post_status,
		'to' => $new_status,
		'date' => current_time('mysql'),
		'user' => get_current_user_id()
	);
	
	update_post_meta($post_id, 'workflow_transitions', $transitions);
	
	// Fire actions based on transition
	if ($new_status === 'submitted') {
		do_action('tm_post_submitted', $post_id, $post);
	} else if ($new_status === 'approved') {
		do_action('tm_post_approved', $post_id, $post);
	} else if ($new_status === 'rejected') {
		do_action('tm_post_rejected', $post_id, $post);
	} else if ($new_status === 'publish') {
		do_action('tm_post_published', $post_id, $post);
	}
}
add_action('save_post_test_method', 'tm_process_workflow_status', 10, 3);
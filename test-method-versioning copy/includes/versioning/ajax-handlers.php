<?php
/**
 * AJAX handler for viewing a version
 */
function tm_ajax_view_version() {
	// Check nonce
	if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'tm_version_nonce')) {
		wp_send_json_error('Invalid nonce');
	}
	
	// Check post ID
	if (!isset($_POST['post_id'])) {
		wp_send_json_error('Missing post ID');
	}
	
	// Get post ID and validate
	$post_id = intval($_POST['post_id']);
	$post = get_post($post_id);
	
	if (!$post || $post->post_type !== 'test_method') {
		wp_send_json_error('Invalid post');
	}
	
	// Check permissions
	if (!current_user_can('edit_post', $post_id)) {
		wp_send_json_error('Insufficient permissions');
	}
	
	// Get version
	if (!isset($_POST['version']) || empty($_POST['version'])) {
		wp_send_json_error('Missing version');
	}
	
	$version = sanitize_text_field($_POST['version']);
	
	// Get versions
	$versions = get_post_meta($post_id, 'versions', true);
	
	if (!is_array($versions) || !isset($versions[$version])) {
		wp_send_json_error('Version not found');
	}
	
	// Get version content
	$version_content = $versions[$version]['content'];
	
	// Render blocks for viewing
	$rendered_content = '';
	if (function_exists('do_blocks')) {
		$rendered_content = do_blocks($version_content);
	} else {
		$rendered_content = wpautop($version_content);
	}
	
	wp_send_json_success(array(
		'content' => $rendered_content
	));
}
add_action('wp_ajax_tm_view_version', 'tm_ajax_view_version');

/**
 * AJAX handler for comparing versions
 */
function tm_ajax_compare_versions() {
	// Check nonce
	if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'tm_version_nonce')) {
		wp_send_json_error('Invalid nonce');
	}
	
	// Check post ID
	if (!isset($_POST['post_id'])) {
		wp_send_json_error('Missing post ID');
	}
	
	// Get post ID and validate
	$post_id = intval($_POST['post_id']);
	$post = get_post($post_id);
	
	if (!$post || $post->post_type !== 'test_method') {
		wp_send_json_error('Invalid post');
	}
	
	// Check permissions
	if (!current_user_can('edit_post', $post_id)) {
		wp_send_json_error('Insufficient permissions');
	}
	
	// Get versions
	if (!isset($_POST['version1']) || empty($_POST['version1']) || !isset($_POST['version2']) || empty($_POST['version2'])) {
		wp_send_json_error('Missing version parameters');
	}
	
	$version1 = sanitize_text_field($_POST['version1']);
	$version2 = sanitize_text_field($_POST['version2']);
	
	// Get versions data
	$versions = get_post_meta($post_id, 'versions', true);
	
	if (!is_array($versions) || !isset($versions[$version1]) || !isset($versions[$version2])) {
		wp_send_json_error('One or both versions not found');
	}
	
	// Get content for both versions
	$content1 = $versions[$version1]['content'];
	$content2 = $versions[$version2]['content'];
	
	// Generate text diff
	if (!class_exists('WP_Text_Diff_Renderer_Table')) {
		require_once ABSPATH . WPINC . '/wp-diff.php';
	}
	
	$args = array(
		'title_left' => __('Version', 'test-method-versioning') . ' ' . $version1,
		'title_right' => __('Version', 'test-method-versioning') . ' ' . $version2,
	);
	
	$left_lines = explode("\n", $content1);
	$right_lines = explode("\n", $content2);
	
	$diff = new WP_Text_Diff_Renderer_Table($args);
	$comparison = $diff->render(new Text_Diff($left_lines, $right_lines));
	
	wp_send_json_success(array(
		'comparison' => $comparison
	));
}
add_action('wp_ajax_tm_compare_versions', 'tm_ajax_compare_versions');

/**
 * AJAX handler for creating a new version
 */
function tm_ajax_create_version() {
	// Check nonce
	if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'astp_version_action')) {
		wp_send_json_error('Invalid nonce');
	}
	
	// Check required parameters
	if (!isset($_POST['post_id']) || !isset($_POST['document_type']) || !isset($_POST['version_type'])) {
		wp_send_json_error('Missing required parameters');
	}
	
	$post_id = intval($_POST['post_id']);
	$document_type = sanitize_text_field($_POST['document_type']);
	$version_type = sanitize_text_field($_POST['version_type']);
	
	// Validate document type
	if (!in_array($document_type, array('ccg', 'tp'))) {
		wp_send_json_error('Invalid document type');
	}
	
	// Validate version type
	if (!in_array($version_type, array('minor', 'major', 'hotfix'))) {
		wp_send_json_error('Invalid version type');
	}
	
	// Get current version info
	$current_version = get_post_meta($post_id, $document_type . '_version', true) ?: '1.0.0';
	
	// Create new version
	$new_version = tm_increment_version($current_version, $version_type);
	
	// Create a new post for this version
	$parent_post = get_post($post_id);
	
	if (!$parent_post) {
		wp_send_json_error('Invalid parent post');
	}
	
	$document_type_name = $document_type === 'ccg' ? 'Certification Companion Guide' : 'Test Procedure';
	
	$version_post_args = array(
		'post_title'    => sprintf('%s – v%s', $parent_post->post_title, $new_version),
		'post_content'  => $parent_post->post_content,
		'post_status'   => 'draft',
		'post_type'     => 'test_method',
		'post_author'   => get_current_user_id(),
		'post_parent'   => $post_id,
		'meta_input'    => array(
			'document_type' => $document_type,
			'version' => $new_version,
			'version_type' => $version_type,
			'version_date' => current_time('mysql'),
			'version_status' => 'Development',
			'parent_test_method' => $post_id
		)
	);
	
	$version_post_id = wp_insert_post($version_post_args);
	
	if (is_wp_error($version_post_id)) {
		wp_send_json_error($version_post_id->get_error_message());
	}
	
	// Update parent post meta
	update_post_meta($post_id, $document_type . '_in_development', $version_post_id);
	
	// Add to version history
	$versions = get_post_meta($post_id, $document_type . '_versions', true);
	if (!is_array($versions)) {
		$versions = array();
	}
	
	$versions[$version_post_id] = array(
		'version' => $new_version,
		'date' => current_time('mysql'),
		'type' => $version_type,
		'status' => 'Development'
	);
	
	update_post_meta($post_id, $document_type . '_versions', $versions);
	
	wp_send_json_success(array(
		'version_id' => $version_post_id,
		'version' => $new_version,
		'edit_url' => get_edit_post_link($version_post_id, 'raw')
	));
}
add_action('wp_ajax_tm_create_version', 'tm_ajax_create_version');

/**
 * AJAX handler for creating a hotfix
 */
function tm_ajax_create_hotfix() {
	// Check nonce
	if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'astp_version_action')) {
		wp_send_json_error('Invalid nonce');
	}
	
	// Check required parameters
	if (!isset($_POST['post_id']) || !isset($_POST['document_type']) || !isset($_POST['version_id'])) {
		wp_send_json_error('Missing required parameters');
	}
	
	$post_id = intval($_POST['post_id']);
	$document_type = sanitize_text_field($_POST['document_type']);
	$version_id = intval($_POST['version_id']);
	
	// Validate document type
	if (!in_array($document_type, array('ccg', 'tp'))) {
		wp_send_json_error('Invalid document type');
	}
	
	// Get version info
	$version_post = get_post($version_id);
	if (!$version_post) {
		wp_send_json_error('Invalid version post');
	}
	
	$current_version = get_post_meta($version_id, 'version', true) ?: '1.0.0';
	
	// Create hotfix version
	$hotfix_version = tm_increment_version($current_version, 'hotfix');
	
	// Create a new post for this hotfix
	$parent_post = get_post($post_id);
	if (!$parent_post) {
		wp_send_json_error('Invalid parent post');
	}
	
	$document_type_name = $document_type === 'ccg' ? 'Certification Companion Guide' : 'Test Procedure';
	
	$hotfix_post_args = array(
		'post_title'    => sprintf('%s – v%s', $parent_post->post_title, $hotfix_version),
		'post_content'  => $version_post->post_content,
		'post_status'   => 'draft',
		'post_type'     => 'test_method',
		'post_author'   => get_current_user_id(),
		'post_parent'   => $post_id,
		'meta_input'    => array(
			'document_type' => $document_type,
			'version' => $hotfix_version,
			'version_type' => 'hotfix',
			'version_date' => current_time('mysql'),
			'version_status' => 'Development',
			'parent_test_method' => $post_id,
			'hotfix_of' => $version_id
		)
	);
	
	$hotfix_post_id = wp_insert_post($hotfix_post_args);
	
	if (is_wp_error($hotfix_post_id)) {
		wp_send_json_error($hotfix_post_id->get_error_message());
	}
	
	// Update parent post meta
	update_post_meta($post_id, $document_type . '_in_development', $hotfix_post_id);
	
	// Add to version history
	$versions = get_post_meta($post_id, $document_type . '_versions', true);
	if (!is_array($versions)) {
		$versions = array();
	}
	
	$versions[$hotfix_post_id] = array(
		'version' => $hotfix_version,
		'date' => current_time('mysql'),
		'type' => 'hotfix',
		'status' => 'Development',
		'hotfix_of' => $version_id
	);
	
	update_post_meta($post_id, $document_type . '_versions', $versions);
	
	wp_send_json_success(array(
		'version_id' => $hotfix_post_id,
		'version' => $hotfix_version,
		'edit_url' => get_edit_post_link($hotfix_post_id, 'raw')
	));
}
add_action('wp_ajax_tm_create_hotfix', 'tm_ajax_create_hotfix');

/**
 * AJAX handler for viewing version history
 */
function tm_ajax_view_version_history() {
	// Check nonce
	if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'astp_version_action')) {
		wp_send_json_error('Invalid nonce');
	}
	
	// Check required parameters
	if (!isset($_POST['post_id']) || !isset($_POST['document_type'])) {
		wp_send_json_error('Missing required parameters');
	}
	
	$post_id = intval($_POST['post_id']);
	$document_type = sanitize_text_field($_POST['document_type']);
	
	// Validate document type
	if (!in_array($document_type, array('ccg', 'tp'))) {
		wp_send_json_error('Invalid document type');
	}
	
	// Get version history
	$versions = get_post_meta($post_id, $document_type . '_versions', true);
	if (!is_array($versions)) {
		$versions = array();
	}
	
	$document_type_name = $document_type === 'ccg' ? 'Certification Companion Guide' : 'Test Procedure';
	
	$history_html = '<h2>' . esc_html($document_type_name) . ' ' . __('Version History', 'test-method-versioning') . '</h2>';
	
	if (empty($versions)) {
		$history_html .= '<p>' . __('No version history available.', 'test-method-versioning') . '</p>';
	} else {
		$history_html .= '<table class="widefat">';
		$history_html .= '<thead><tr><th>' . __('Version', 'test-method-versioning') . '</th><th>' . __('Date', 'test-method-versioning') . '</th><th>' . __('Type', 'test-method-versioning') . '</th><th>' . __('Status', 'test-method-versioning') . '</th><th>' . __('Actions', 'test-method-versioning') . '</th></tr></thead>';
		$history_html .= '<tbody>';
		
		foreach ($versions as $version_id => $version_data) {
			$version_number = isset($version_data['version']) ? $version_data['version'] : '';
			$version_date = isset($version_data['date']) ? $version_data['date'] : '';
			$version_type = isset($version_data['type']) ? $version_data['type'] : 'Minor';
			$version_status = isset($version_data['status']) ? $version_data['status'] : 'Published';
			
			$history_html .= '<tr>';
			$history_html .= '<td><strong>' . esc_html($version_number) . '</strong></td>';
			$history_html .= '<td>' . esc_html(date_i18n(get_option('date_format'), strtotime($version_date))) . '</td>';
			$history_html .= '<td>' . esc_html(ucfirst($version_type)) . '</td>';
			$history_html .= '<td>' . esc_html(ucfirst($version_status)) . '</td>';
			$history_html .= '<td>';
			$history_html .= '<a href="' . esc_url(get_edit_post_link($version_id)) . '" class="button button-small">' . __('View', 'test-method-versioning') . '</a>';
			
			if ($version_status === 'Published') {
				$history_html .= ' <a href="#" class="button button-small astp-create-version" data-post-id="' . esc_attr($post_id) . '" data-document-type="' . esc_attr($document_type) . '">' . __('New Version', 'test-method-versioning') . '</a>';
				$history_html .= ' <a href="#" class="button button-small astp-create-hotfix" data-version-id="' . esc_attr($version_id) . '" data-post-id="' . esc_attr($post_id) . '" data-document-type="' . esc_attr($document_type) . '">' . __('Hotfix', 'test-method-versioning') . '</a>';
			}
			
			$history_html .= '</td>';
			$history_html .= '</tr>';
		}
		
		$history_html .= '</tbody></table>';
	}
	
	wp_send_json_success(array(
		'history_html' => $history_html
	));
}
add_action('wp_ajax_tm_view_version_history', 'tm_ajax_view_version_history');

/**
 * Increment version number
 */
function tm_increment_version($version, $type = 'minor') {
	$parts = explode('.', $version);
	
	// Ensure we have at least 3 parts (semver)
	while (count($parts) < 3) {
		$parts[] = '0';
	}
	
	$major = (int) $parts[0];
	$minor = (int) $parts[1];
	$patch = (int) $parts[2];
	
	switch ($type) {
		case 'major':
			$major++;
			$minor = 0;
			$patch = 0;
			break;
		
		case 'minor':
			$minor++;
			$patch = 0;
			break;
		
		case 'hotfix':
		case 'patch':
			$patch++;
			break;
	}
	
	return $major . '.' . $minor . '.' . $patch;
}

/**
 * AJAX handler for creating a new version
 */
function tm_ajax_create_version() {
	// Check nonce
	if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'astp_version_action')) {
		wp_send_json_error('Invalid nonce');
	}
	
	// Check required parameters
	if (!isset($_POST['post_id']) || !isset($_POST['document_type']) || !isset($_POST['version_type'])) {
		wp_send_json_error('Missing required parameters');
	}
	
	$post_id = intval($_POST['post_id']);
	$document_type = sanitize_text_field($_POST['document_type']);
	$version_type = sanitize_text_field($_POST['version_type']);
	
	// Validate document type
	if (!in_array($document_type, array('ccg', 'tp'))) {
		wp_send_json_error('Invalid document type');
	}
	
	// Validate version type
	if (!in_array($version_type, array('minor', 'major', 'hotfix'))) {
		wp_send_json_error('Invalid version type');
	}
	
	// Create new version
	$result = tm_create_version($post_id, $document_type, $version_type);
	
	if ($result) {
		wp_send_json_success($result);
	} else {
		wp_send_json_error('Failed to create version');
	}
}
add_action('wp_ajax_tm_create_version', 'tm_ajax_create_version');

/**
 * AJAX handler for creating a hotfix
 */
function tm_ajax_create_hotfix() {
	// Check nonce
	if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'astp_version_action')) {
		wp_send_json_error('Invalid nonce');
	}
	
	// Check required parameters
	if (!isset($_POST['post_id']) || !isset($_POST['document_type']) || !isset($_POST['version'])) {
		wp_send_json_error('Missing required parameters');
	}
	
	$post_id = intval($_POST['post_id']);
	$document_type = sanitize_text_field($_POST['document_type']);
	$version = sanitize_text_field($_POST['version']);
	
	// Validate document type
	if (!in_array($document_type, array('ccg', 'tp'))) {
		wp_send_json_error('Invalid document type');
	}
	
	// Create hotfix
	$result = tm_create_hotfix($post_id, $document_type, $version);
	
	if ($result) {
		wp_send_json_success($result);
	} else {
		wp_send_json_error('Failed to create hotfix');
	}
}
add_action('wp_ajax_tm_create_hotfix', 'tm_ajax_create_hotfix');

/**
 * AJAX handler for viewing version details
 */
function tm_ajax_view_version_details() {
	// Check nonce
	if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'astp_version_action')) {
		wp_send_json_error('Invalid nonce');
	}
	
	// Check required parameters
	if (!isset($_POST['post_id']) || !isset($_POST['document_type']) || !isset($_POST['version'])) {
		wp_send_json_error('Missing required parameters');
	}
	
	$post_id = intval($_POST['post_id']);
	$document_type = sanitize_text_field($_POST['document_type']);
	$version = sanitize_text_field($_POST['version']);
	
	// Validate document type
	if (!in_array($document_type, array('ccg', 'tp'))) {
		wp_send_json_error('Invalid document type');
	}
	
	// Get version data
	$versions_key = $document_type . '_versions';
	$versions = get_post_meta($post_id, $versions_key, true);
	
	if (!is_array($versions) || !isset($versions[$version])) {
		wp_send_json_error('Version not found');
	}
	
	$version_data = $versions[$version];
	
	// Get block version data
	$block_version = tm_get_block_version($post_id, $document_type, $version);
	
	$document_type_name = $document_type === 'ccg' ? 'Certification Companion Guide' : 'Test Procedure';
	
	$html = '<div class="astp-version-details">';
	$html .= '<h2>' . esc_html($document_type_name) . ' ' . __('Version', 'test-method-versioning') . ' ' . esc_html($version) . '</h2>';
	
	$html .= '<div class="astp-version-metadata">';
	$html .= '<p><strong>' . __('Created:', 'test-method-versioning') . '</strong> ' . esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($version_data['date']))) . '</p>';
	
	if (isset($version_data['author'])) {
		$author = get_user_by('id', $version_data['author']);
		if ($author) {
			$html .= '<p><strong>' . __('Author:', 'test-method-versioning') . '</strong> ' . esc_html($author->display_name) . '</p>';
		}
	}
	
	if (isset($version_data['type'])) {
		$html .= '<p><strong>' . __('Update Type:', 'test-method-versioning') . '</strong> ' . esc_html(ucfirst($version_data['type'])) . '</p>';
	}
	
	$html .= '</div>';
	
	// Get changelog
	$block_versions = get_post_meta($post_id, 'block_versions', true);
	$key = $document_type . '_' . $version;
	
	if (is_array($block_versions) && isset($block_versions[$key]) && isset($block_versions[$key]['changelog'])) {
		$changelog = $block_versions[$key]['changelog'];
		
		if (!empty($changelog)) {
			$html .= '<div class="astp-version-changelog">';
			$html .= '<h3>' . __('Changes', 'test-method-versioning') . '</h3>';
			$html .= '<ul>';
			
			foreach ($changelog as $change) {
				$class = 'astp-change-' . $change['type'];
				$html .= '<li class="' . esc_attr($class) . '">' . esc_html($change['message']) . '</li>';
			}
			
			$html .= '</ul>';
			$html .= '</div>';
		}
	}
	
	// Display content preview
	if (isset($version_data['content'])) {
		$html .= '<div class="astp-version-content-preview">';
		$html .= '<h3>' . __('Content Preview', 'test-method-versioning') . '</h3>';
		
		if (function_exists('do_blocks')) {
			$html .= do_blocks($version_data['content']);
		} else {
			$html .= wpautop($version_data['content']);
		}
		
		$html .= '</div>';
	}
	
	$html .= '</div>';
	
	wp_send_json_success(array(
		'html' => $html
	));
}
add_action('wp_ajax_tm_view_version_details', 'tm_ajax_view_version_details');

/**
 * AJAX handler for viewing version history
 */
function tm_ajax_view_version_history() {
	// Check nonce
	if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'astp_version_action')) {
		wp_send_json_error('Invalid nonce');
	}
	
	// Check required parameters
	if (!isset($_POST['post_id']) || !isset($_POST['document_type'])) {
		wp_send_json_error('Missing required parameters');
	}
	
	$post_id = intval($_POST['post_id']);
	$document_type = sanitize_text_field($_POST['document_type']);
	
	// Validate document type
	if (!in_array($document_type, array('ccg', 'tp'))) {
		wp_send_json_error('Invalid document type');
	}
	
	// Get version history
	$versions_key = $document_type . '_versions';
	$versions = get_post_meta($post_id, $versions_key, true);
	
	if (!is_array($versions)) {
		$versions = array();
	}
	
	$document_type_name = $document_type === 'ccg' ? 'Certification Companion Guide' : 'Test Procedure';
	$current_version = get_post_meta($post_id, $document_type . '_version', true) ?: '1.0';
	
	$html = '<div class="astp-version-history">';
	$html .= '<h2>' . esc_html($document_type_name) . ' ' . __('Version History', 'test-method-versioning') . '</h2>';
	
	if (empty($versions)) {
		$html .= '<p>' . __('No version history available.', 'test-method-versioning') . '</p>';
	} else {
		$html .= '<table class="widefat">';
		$html .= '<thead><tr><th>' . __('Version', 'test-method-versioning') . '</th><th>' . __('Date', 'test-method-versioning') . '</th><th>' . __('Type', 'test-method-versioning') . '</th><th>' . __('Status', 'test-method-versioning') . '</th><th>' . __('Actions', 'test-method-versioning') . '</th></tr></thead>';
		$html .= '<tbody>';
		
		foreach ($versions as $version => $version_data) {
			$date = isset($version_data['date']) ? $version_data['date'] : current_time('mysql');
			$type = isset($version_data['type']) ? $version_data['type'] : 'Minor';
			$status = ($version === $current_version) ? 'Published' : 'Archived';
			
			$html .= '<tr>';
			$html .= '<td><strong>' . esc_html($version) . '</strong></td>';
			$html .= '<td>' . esc_html(date_i18n(get_option('date_format'), strtotime($date))) . '</td>';
			$html .= '<td>' . esc_html(ucfirst($type)) . '</td>';
			$html .= '<td>' . esc_html($status) . '</td>';
			$html .= '<td>';
			$html .= '<a href="#" class="button button-small astp-view-version-details" data-document-type="' . esc_attr($document_type) . '" data-version="' . esc_attr($version) . '">' . __('View', 'test-method-versioning') . '</a>';
			
			if ($status === 'Published') {
				$html .= ' <a href="#" class="button button-small astp-create-version" data-document-type="' . esc_attr($document_type) . '">' . __('New Version', 'test-method-versioning') . '</a>';
				$html .= ' <a href="#" class="button button-small astp-create-hotfix" data-document-type="' . esc_attr($document_type) . '" data-version="' . esc_attr($version) . '">' . __('Hotfix', 'test-method-versioning') . '</a>';
			} else {
				$html .= ' <a href="#" class="button button-small astp-restore-version" data-document-type="' . esc_attr($document_type) . '" data-version="' . esc_attr($version) . '">' . __('Restore', 'test-method-versioning') . '</a>';
			}
			
			$html .= '</td>';
			$html .= '</tr>';
		}
		
		$html .= '</tbody></table>';
	}
	
	$html .= '</div>';
	
	wp_send_json_success(array(
		'html' => $html
	));
}
add_action('wp_ajax_tm_view_version_history', 'tm_ajax_view_version_history');

/**
 * AJAX handler for restoring a version
 */
function tm_ajax_restore_version() {
	// Check nonce
	if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'astp_version_action')) {
		wp_send_json_error('Invalid nonce');
	}
	
	// Check required parameters
	if (!isset($_POST['post_id']) || !isset($_POST['document_type']) || !isset($_POST['version'])) {
		wp_send_json_error('Missing required parameters');
	}
	
	$post_id = intval($_POST['post_id']);
	$document_type = sanitize_text_field($_POST['document_type']);
	$version = sanitize_text_field($_POST['version']);
	
	// Validate document type
	if (!in_array($document_type, array('ccg', 'tp'))) {
		wp_send_json_error('Invalid document type');
	}
	
	// Restore version
	$result = tm_restore_version($post_id, $document_type, $version);
	
	if ($result) {
		wp_send_json_success(array(
			'message' => __('Version restored successfully.', 'test-method-versioning')
		));
	} else {
		wp_send_json_error('Failed to restore version');
	}
}
add_action('wp_ajax_tm_restore_version', 'tm_ajax_restore_version');
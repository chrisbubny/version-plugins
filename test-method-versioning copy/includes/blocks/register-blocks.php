<?php
/**
 * Register blocks for the plugin
 */

/**
 * Register custom blocks
 */
function tm_register_blocks() {
	// Only register script if Gutenberg is active
	if (!function_exists('register_block_type')) {
		return;
	}
	
	// Register changelog block
	register_block_type('test-method-versioning/changelog', array(
		'editor_script' => 'tm-changelog-block',
		'attributes' => array(
			'content' => array(
				'type' => 'string',
				'default' => '',
			),
			'blockId' => array(
				'type' => 'string',
			),
		),
		'render_callback' => 'tm_render_changelog_block',
	));
	
	// Register version info block
	register_block_type('test-method-versioning/version-info', array(
		'editor_script' => 'tm-version-info-block',
		'attributes' => array(
			'content' => array(
				'type' => 'string',
				'default' => '',
			),
			'blockId' => array(
				'type' => 'string',
			),
		),
		'render_callback' => 'tm_render_version_info_block',
	));
}

/**
 * Enqueue block editor assets
 */
function tm_enqueue_block_assets() {
	// Only load for test_method post type in editor
	global $post;
	if (!is_admin() || !$post || get_post_type($post) !== 'test_method') {
		return;
	}
	
	// Register and enqueue block scripts
	wp_register_script(
		'tm-changelog-block',
		TM_PLUGIN_URL . 'assets/js/blocks/changelog.js',
		array('wp-blocks', 'wp-element', 'wp-editor', 'wp-components'),
		TM_VERSION
	);
	
	wp_register_script(
		'tm-version-info-block',
		TM_PLUGIN_URL . 'assets/js/blocks/version-info.js',
		array('wp-blocks', 'wp-element', 'wp-editor', 'wp-components'),
		TM_VERSION
	);
	
	// Localize script with version info
	wp_localize_script('tm-changelog-block', 'tmVersioningData', array(
		'restURL' => rest_url('tm-versioning/v1'),
		'nonce' => wp_create_nonce('wp_rest'),
		'postId' => $post->ID,
	));
	
	wp_localize_script('tm-version-info-block', 'tmVersioningData', array(
		'restURL' => rest_url('tm-versioning/v1'),
		'nonce' => wp_create_nonce('wp_rest'),
		'postId' => $post->ID,
	));
}
add_action('enqueue_block_editor_assets', 'tm_enqueue_block_assets');

/**
 * Render changelog block
 */
function tm_render_changelog_block($attributes, $content) {
	global $post;
	
	if (!$post || get_post_type($post) !== 'test_method') {
		return '';
	}
	
	// Get changelog data
	$changelog = get_post_meta($post->ID, 'changelog', true);
	if (!is_array($changelog) || empty($changelog)) {
		return '<div class="tm-changelog-block"><p>' . __('No changelog data available.', 'test-method-versioning') . '</p></div>';
	}
	
	// Get current version
	$ccg_version = get_post_meta($post->ID, 'ccg_version', true) ?: '1.0.0';
	$tp_version = get_post_meta($post->ID, 'tp_version', true) ?: '1.0.0';
	$current_version = $ccg_version . '-' . $tp_version;
	
	// Build output
	$output = '<div class="tm-changelog-block">';
	$output .= '<h3>' . __('Changelog', 'test-method-versioning') . '</h3>';
	
	// Sort versions by date (newest first)
	uasort($changelog, function($a, $b) {
		return strtotime($b['date']) - strtotime($a['date']);
	});
	
	foreach ($changelog as $version => $data) {
		$is_current = ($version === $current_version);
		$date = isset($data['date']) ? date_i18n(get_option('date_format'), strtotime($data['date'])) : '';
		$type = isset($data['type']) ? $data['type'] : '';
		
		$output .= '<div class="tm-changelog-version' . ($is_current ? ' tm-current-version' : '') . '">';
		$output .= '<h4>' . esc_html($version) . ($is_current ? ' <span class="tm-current-label">' . __('(Current)', 'test-method-versioning') . '</span>' : '') . '</h4>';
		$output .= '<p class="tm-changelog-meta">';
		$output .= '<span class="tm-changelog-date">' . esc_html($date) . '</span>';
		if ($type) {
			$output .= ' <span class="tm-changelog-type">' . esc_html(ucfirst($type)) . '</span>';
		}
		$output .= '</p>';
		
		if (isset($data['changes']) && is_array($data['changes'])) {
			$changes = $data['changes'];
			
			if (!empty($changes['added'])) {
				$output .= '<div class="tm-changelog-added">';
				$output .= '<h5>' . __('Added', 'test-method-versioning') . '</h5>';
				$output .= '<ul>';
				
				foreach ($changes['added'] as $block) {
					$content = isset($block['innerHTML']) ? strip_tags($block['innerHTML']) : '';
					$excerpt = wp_trim_words($content, 10);
					$output .= '<li>' . esc_html($excerpt) . '</li>';
				}
				
				$output .= '</ul>';
				$output .= '</div>';
			}
			
			if (!empty($changes['modified'])) {
				$output .= '<div class="tm-changelog-modified">';
				$output .= '<h5>' . __('Modified', 'test-method-versioning') . '</h5>';
				$output .= '<ul>';
				
				foreach ($changes['modified'] as $modification) {
					$old_content = isset($modification['old']['innerHTML']) ? strip_tags($modification['old']['innerHTML']) : '';
					$new_content = isset($modification['new']['innerHTML']) ? strip_tags($modification['new']['innerHTML']) : '';
					
					$old_excerpt = wp_trim_words($old_content, 5);
					$new_excerpt = wp_trim_words($new_content, 5);
					
					$output .= '<li>';
					$output .= '<span class="tm-changelog-old">' . esc_html($old_excerpt) . '</span>';
					$output .= ' <span class="tm-changelog-arrow">&rarr;</span> ';
					$output .= '<span class="tm-changelog-new">' . esc_html($new_excerpt) . '</span>';
					$output .= '</li>';
				}
				
				$output .= '</ul>';
				$output .= '</div>';
			}
			
			if (!empty($changes['deleted'])) {
				$output .= '<div class="tm-changelog-deleted">';
				$output .= '<h5>' . __('Deleted', 'test-method-versioning') . '</h5>';
				$output .= '<ul>';
				
				foreach ($changes['deleted'] as $block) {
					$content = isset($block['innerHTML']) ? strip_tags($block['innerHTML']) : '';
					$excerpt = wp_trim_words($content, 10);
					$output .= '<li>' . esc_html($excerpt) . '</li>';
				}
				
				$output .= '</ul>';
				$output .= '</div>';
			}
		} else {
			$output .= '<p>' . __('No change details available.', 'test-method-versioning') . '</p>';
		}
		
		$output .= '</div>';
	}
	
	$output .= '</div>';
	
	return $output;
}

/**
 * Render version info block
 */
function tm_render_version_info_block($attributes, $content) {
	global $post;
	
	if (!$post || get_post_type($post) !== 'test_method') {
		return '';
	}
	
	// Get version data
	$ccg_version = get_post_meta($post->ID, 'ccg_version', true) ?: '1.0.0';
	$tp_version = get_post_meta($post->ID, 'tp_version', true) ?: '1.0.0';
	$workflow_status = get_post_meta($post->ID, 'workflow_status', true) ?: 'draft';
	
	// Get approvals
	$approvals = get_post_meta($post->ID, 'approvals', true);
	$approval_count = 0;
	
	if (is_array($approvals)) {
		foreach ($approvals as $approval) {
			if ($approval['status'] === 'approved') {
				$approval_count++;
			}
		}
	}
	
	// Build output
	$output = '<div class="tm-version-info-block">';
	$output .= '<h3>' . __('Version Information', 'test-method-versioning') . '</h3>';
	
	$output .= '<div class="tm-version-details">';
	$output .= '<p><strong>' . __('CCG Version:', 'test-method-versioning') . '</strong> ' . esc_html($ccg_version) . '</p>';
	$output .= '<p><strong>' . __('TP Version:', 'test-method-versioning') . '</strong> ' . esc_html($tp_version) . '</p>';
	
	// Status with badge
	$status_class = 'tm-status-' . $workflow_status;
	$status_label = '';
	
	switch ($workflow_status) {
		case 'draft':
			$status_label = __('Draft', 'test-method-versioning');
			break;
		case 'submitted':
			$status_label = __('Submitted for Review', 'test-method-versioning');
			break;
		case 'approved':
			$status_label = __('Approved', 'test-method-versioning');
			break;
		case 'rejected':
			$status_label = __('Rejected', 'test-method-versioning');
			break;
		case 'published':
			$status_label = __('Published', 'test-method-versioning');
			break;
		case 'revision':
			$status_label = __('In Revision', 'test-method-versioning');
			break;
		default:
			$status_label = ucfirst($workflow_status);
			break;
	}
	
	$output .= '<p><strong>' . __('Status:', 'test-method-versioning') . '</strong> ';
	$output .= '<span class="tm-status-badge ' . $status_class . '">' . esc_html($status_label) . '</span></p>';
	
	if ($approval_count > 0) {
		$output .= '<p><strong>' . __('Approvals:', 'test-method-versioning') . '</strong> ' . $approval_count . ' / 2</p>';
	}
	
	$output .= '</div>';
	$output .= '</div>';
	
	return $output;
}
<?php
/**
 * Handles version control
 */

/**
 * Create a new version
 */
function tm_create_version($post_id, $version_type = 'minor') {
	// Get post content
	$post = get_post($post_id);
	$content = $post->post_content;
	
	// Parse blocks
	$blocks = parse_blocks($content);
	
	// Get current versions
	$ccg_version = get_post_meta($post_id, 'ccg_version', true) ?: '1.0.0';
	$tp_version = get_post_meta($post_id, 'tp_version', true) ?: '1.0.0';
	
	// Generate new versions based on version type
	$new_ccg_version = tm_increment_version($ccg_version, $version_type);
	$new_tp_version = tm_increment_version($tp_version, $version_type);
	
	// Store previous version content
	$previous_versions = get_post_meta($post_id, 'versions', true);
	if (!is_array($previous_versions)) {
		$previous_versions = array();
	}
	
	// Add current content as a version
	$previous_versions[$ccg_version . '-' . $tp_version] = array(
		'date' => current_time('mysql'),
		'content' => $content,
		'blocks' => $blocks
	);
	
	// Store versions
	update_post_meta($post_id, 'versions', $previous_versions);
	
	// Update version numbers
	update_post_meta($post_id, 'ccg_version', $new_ccg_version);
	update_post_meta($post_id, 'tp_version', $new_tp_version);
	
	// Calculate changes if we have a previous version
	if (count($previous_versions) > 1) {
		// Get keys of versions array
		$version_keys = array_keys($previous_versions);
		
		// Get previous version
		$previous_version_key = $version_keys[count($version_keys) - 2];
		$previous_version = $previous_versions[$previous_version_key];
		
		// Compare blocks
		$changes = tm_compare_blocks($previous_version['blocks'], $blocks);
		
		// Store changes
		$changelog = get_post_meta($post_id, 'changelog', true);
		if (!is_array($changelog)) {
			$changelog = array();
		}
		
		$changelog[$new_ccg_version . '-' . $new_tp_version] = array(
			'date' => current_time('mysql'),
			'type' => $version_type,
			'changes' => $changes
		);
		
		update_post_meta($post_id, 'changelog', $changelog);
	}
	
	do_action('tm_version_created', $post_id, $new_ccg_version, $new_tp_version);
	
	return array(
		'ccg_version' => $new_ccg_version,
		'tp_version' => $new_tp_version
	);
}

/**
 * Increment version number
 */
function tm_increment_version($version, $type = 'minor') {
	$parts = explode('.', $version);
	
	// Ensure we have a valid semver format
	if (count($parts) !== 3) {
		return '1.0.0';
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
		case 'patch':
		case 'hotfix':
			$patch++;
			break;
		case 'basic':
			// No version change
			break;
	}
	
	return $major . '.' . $minor . '.' . $patch;
}

/**
 * Rollback to a specific version
 */
function tm_rollback_to_version($post_id, $version) {
	// Get versions
	$versions = get_post_meta($post_id, 'versions', true);
	
	// Check if version exists
	if (!isset($versions[$version])) {
		return false;
	}
	
	// Get version content
	$version_data = $versions[$version];
	$content = $version_data['content'];
	
	// Update post with version content
	$post = array(
		'ID' => $post_id,
		'post_content' => $content
	);
	
	wp_update_post($post);
	
	// Get version numbers
	$version_parts = explode('-', $version);
	$ccg_version = $version_parts[0];
	$tp_version = $version_parts[1];
	
	// Update version meta
	update_post_meta($post_id, 'ccg_version', $ccg_version);
	update_post_meta($post_id, 'tp_version', $tp_version);
	
	do_action('tm_version_rolled_back', $post_id, $version);
	
	return true;
}
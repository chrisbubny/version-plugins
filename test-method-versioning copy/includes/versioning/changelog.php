<?php
/**
 * Handles changelog generation
 */
/**
 * Generate changelog for a version
 */
function tm_generate_changelog($post_id, $ccg_version, $tp_version) {
	// Get changelog
	$changelog = get_post_meta($post_id, 'changelog', true);
	if (!is_array($changelog)) {
		$changelog = array();
	}
	
	// Check if changelog already exists for this version
	$version = $ccg_version . '-' . $tp_version;
	if (isset($changelog[$version])) {
		return $changelog[$version];
	}
	
	// Get versions
	$versions = get_post_meta($post_id, 'versions', true);
	if (!is_array($versions)) {
		return false;
	}
	
	// Check if this version exists
	if (!isset($versions[$version])) {
		return false;
	}
	
	// Get previous version
	$version_keys = array_keys($versions);
	$current_index = array_search($version, $version_keys);
	
	// If this is the first version, there's no changelog
	if ($current_index === 0 || $current_index === false) {
		return false;
	}
	
	$previous_version_key = $version_keys[$current_index - 1];
	$previous_version = $versions[$previous_version_key];
	$current_version = $versions[$version];
	
	// Compare blocks
	$changes = tm_compare_blocks($previous_version['blocks'], $current_version['blocks']);
	
	// Store changes
	$changelog[$version] = array(
		'date' => current_time('mysql'),
		'previous_version' => $previous_version_key,
		'changes' => $changes
	);
	
	update_post_meta($post_id, 'changelog', $changelog);
	
	return $changelog[$version];
}

/**
 * Get changelog for a version
 */
function tm_get_changelog($post_id, $version = '') {
	// Get changelog
	$changelog = get_post_meta($post_id, 'changelog', true);
	if (!is_array($changelog)) {
		return false;
	}
	
	// If no version specified, return all
	if (empty($version)) {
		return $changelog;
	}
	
	// Check if changelog exists for this version
	if (isset($changelog[$version])) {
		return $changelog[$version];
	}
	
	return false;
}

/**
 * Format changelog for display
 */
function tm_format_changelog($changelog) {
	if (!is_array($changelog) || !isset($changelog['changes'])) {
		return '';
	}
	
	$changes = $changelog['changes'];
	$output = '';
	
	// Added blocks
	if (!empty($changes['added'])) {
		$output .= '<h3>' . __('Added', 'test-method-versioning') . '</h3>';
		$output .= '<ul class="tm-changelog-added">';
		
		foreach ($changes['added'] as $block) {
			$content = isset($block['innerHTML']) ? strip_tags($block['innerHTML']) : '';
			$output .= '<li>' . esc_html(wp_trim_words($content, 10)) . '</li>';
		}
		
		$output .= '</ul>';
	}
	
	// Modified blocks
	if (!empty($changes['modified'])) {
		$output .= '<h3>' . __('Modified', 'test-method-versioning') . '</h3>';
		$output .= '<ul class="tm-changelog-modified">';
		
		foreach ($changes['modified'] as $modification) {
			$old_content = isset($modification['old']['innerHTML']) ? strip_tags($modification['old']['innerHTML']) : '';
			$new_content = isset($modification['new']['innerHTML']) ? strip_tags($modification['new']['innerHTML']) : '';
			
			$output .= '<li>';
			$output .= '<div class="tm-changelog-old">' . esc_html(wp_trim_words($old_content, 10)) . '</div>';
			$output .= '<div class="tm-changelog-arrow">→</div>';
			$output .= '<div class="tm-changelog-new">' . esc_html(wp_trim_words($new_content, 10)) . '</div>';
			$output .= '</li>';
		}
		
		$output .= '</ul>';
	}
	
	// Deleted blocks
	if (!empty($changes['deleted'])) {
		$output .= '<h3>' . __('Deleted', 'test-method-versioning') . '</h3>';
		$output .= '<ul class="tm-changelog-deleted">';
		
		foreach ($changes['deleted'] as $block) {
			$content = isset($block['innerHTML']) ? strip_tags($block['innerHTML']) : '';
			$output .= '<li>' . esc_html(wp_trim_words($content, 10)) . '</li>';
		}
		
		$output .= '</ul>';
	}
	
	return $output;
}
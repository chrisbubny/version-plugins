<?php
/**
 * Handles content locking
 */
/**
 * Lock a post for editing
 */
function tm_lock_post($post_id) {
	$user_id = get_current_user_id();
	$lock_time = time();
	
	// Set post lock
	update_post_meta($post_id, '_edit_lock', $lock_time . ':' . $user_id);
	
	return true;
}

/**
 * Unlock a post for editing
 */
function tm_unlock_post($post_id) {
	// Remove post lock
	delete_post_meta($post_id, '_edit_lock');
	
	return true;
}

/**
 * Check if post is locked
 */
function tm_is_post_locked($post_id) {
	$lock = get_post_meta($post_id, '_edit_lock', true);
	
	if (!$lock) {
		return false;
	}
	
	$user_id = get_current_user_id();
	list($lock_time, $lock_user) = explode(':', $lock);
	
	// If current user is the one who locked it, it's not locked for them
	if ($lock_user == $user_id) {
		return false;
	}
	
	return true;
}

/**
 * Filter editing capabilities based on workflow state and post lock
 */
function tm_filter_edit_capabilities($allcaps, $caps, $args) {
	// Check if we're checking a post editing capability
	if (isset($caps[0]) && in_array($caps[0], array('edit_post', 'delete_post', 'edit_test_method'))) {
		if (!isset($args[2])) {
			return $allcaps;
		}
		
		$post_id = $args[2];
		$post = get_post($post_id);
		
		// Only apply to test_method post type
		if (!$post || $post->post_type !== 'test_method') {
			return $allcaps;
		}
		
		// Get workflow status
		$status = get_post_meta($post_id, 'workflow_status', true);
		
		// Check if post is locked
		if (($status === 'published' || $post->post_status === 'publish') && tm_is_post_locked($post_id)) {
			// Get current user roles
			$user = wp_get_current_user();
			$roles = $user->roles;
			
			// Only TP Admin or Administrator can edit published and locked posts
			if (!in_array('tp_admin', $roles) && !in_array('administrator', $roles)) {
				$allcaps['edit_post'] = false;
				$allcaps['delete_post'] = false;
				$allcaps['edit_test_method'] = false;
			}
		}
	}
	
	return $allcaps;
}
add_filter('user_has_cap', 'tm_filter_edit_capabilities', 10, 3);
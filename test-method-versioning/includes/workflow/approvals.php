<?php
/**
 * Handles workflow approvals
 */

/**
 * Record an approval or rejection
 */
function tm_record_approval($post_id, $user_id, $approved, $comment, $version = '') {
	// Get existing approvals
	$approvals = get_post_meta($post_id, 'approvals', true);
	if (!is_array($approvals)) {
		$approvals = array();
	}
	
	// Add new approval
	$approvals[] = array(
		'user_id' => $user_id,
		'date' => current_time('mysql'),
		'status' => $approved ? 'approved' : 'rejected',
		'comments' => $comment,
		'version' => $version
	);
	
	// Save approvals
	update_post_meta($post_id, 'approvals', $approvals);
	
	return true;
}

/**
 * Get approvals for a post
 */
function tm_get_approvals($post_id) {
	$approvals = get_post_meta($post_id, 'approvals', true);
	if (!is_array($approvals)) {
		$approvals = array();
	}
	
	return $approvals;
}

/**
 * Count approvals for a post
 */
function tm_count_approvals($post_id) {
	$approvals = tm_get_approvals($post_id);
	$count = 0;
	
	foreach ($approvals as $approval) {
		if ($approval['status'] === 'approved') {
			$count++;
		}
	}
	
	return $count;
}

/**
 * Check if post has enough approvals
 */
function tm_has_enough_approvals($post_id, $required = 2) {
	return tm_count_approvals($post_id) >= $required;
}
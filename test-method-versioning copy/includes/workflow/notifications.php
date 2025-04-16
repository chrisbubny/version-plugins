<?php
/**
 * Handles workflow notifications
 */

/**
 * Send notification when a post is submitted for review
 */
function tm_notify_submitted_for_review($post_id) {
	$post = get_post($post_id);
	$author = get_userdata($post->post_author);
	
	// Get all users with TP Approver role
	$approvers = get_users(array('role' => 'tp_approver'));
	
	// Add TP Admin users
	$admins = get_users(array('role' => 'tp_admin'));
	$recipients = array_merge($approvers, $admins);
	
	if (empty($recipients)) {
		return false;
	}
	
	$subject = sprintf(__('[%s] New Test Method Submitted for Review: %s', 'test-method-versioning'),
					  get_bloginfo('name'), $post->post_title);
	
	foreach ($recipients as $recipient) {
		$message = sprintf(__('A new test method has been submitted for review by %s.', 'test-method-versioning'),
						  $author->display_name);
		
		$message .= "\n\n";
		$message .= sprintf(__('Title: %s', 'test-method-versioning'), $post->post_title);
		$message .= "\n\n";
		$message .= __('Click the link below to review:', 'test-method-versioning');
		$message .= "\n";
		$message .= admin_url('post.php?post=' . $post_id . '&action=edit');
		
		wp_mail($recipient->user_email, $subject, $message);
	}
	
	return true;
}
add_action('tm_post_submitted_for_review', 'tm_notify_submitted_for_review');

/**
 * Send notification when a post is approved
 */
function tm_notify_approved($post_id) {
	$post = get_post($post_id);
	$author = get_userdata($post->post_author);
	
	// Get TP Admin users
	$admins = get_users(array('role' => 'tp_admin'));
	
	if (empty($admins)) {
		return false;
	}
	
	$subject = sprintf(__('[%s] Test Method Approved: %s', 'test-method-versioning'),
					  get_bloginfo('name'), $post->post_title);
	
	// Notify admins
	foreach ($admins as $admin) {
		$message = sprintf(__('A test method has been approved and is ready for publishing.', 'test-method-versioning'));
		
		$message .= "\n\n";
		$message .= sprintf(__('Title: %s', 'test-method-versioning'), $post->post_title);
		$message .= "\n\n";
		$message .= __('Click the link below to review and publish:', 'test-method-versioning');
		$message .= "\n";
		$message .= admin_url('post.php?post=' . $post_id . '&action=edit');
		
		wp_mail($admin->user_email, $subject, $message);
	}
	
	// Notify author
	$author_subject = sprintf(__('[%s] Your Test Method Has Been Approved: %s', 'test-method-versioning'),
							get_bloginfo('name'), $post->post_title);
	
	$author_message = __('Your test method has been approved and is pending publication.', 'test-method-versioning');
	$author_message .= "\n\n";
	$author_message .= sprintf(__('Title: %s', 'test-method-versioning'), $post->post_title);
	
	wp_mail($author->user_email, $author_subject, $author_message);
	
	return true;
}
add_action('tm_post_approved', 'tm_notify_approved');

/**
 * Send notification when a post is rejected
 */
function tm_notify_rejected($post_id) {
	$post = get_post($post_id);
	$author = get_userdata($post->post_author);
	
	// Get rejection comment
	$approvals = get_post_meta($post_id, 'approvals', true);
	$rejection_comment = '';
	
	if (is_array($approvals)) {
		// Get the most recent rejection
		foreach (array_reverse($approvals) as $approval) {
			if ($approval['status'] === 'rejected') {
				$rejection_comment = $approval['comments'];
				break;
			}
		}
	}
	
	$subject = sprintf(__('[%s] Your Test Method Has Been Rejected: %s', 'test-method-versioning'),
					  get_bloginfo('name'), $post->post_title);
	
	$message = __('Your test method has been rejected and requires revisions.', 'test-method-versioning');
	$message .= "\n\n";
	$message .= sprintf(__('Title: %s', 'test-method-versioning'), $post->post_title);
	
	if (!empty($rejection_comment)) {
		$message .= "\n\n";
		$message .= __('Rejection Comments:', 'test-method-versioning');
		$message .= "\n";
		$message .= $rejection_comment;
	}
	
	$message .= "\n\n";
	$message .= __('Click the link below to edit and resubmit:', 'test-method-versioning');
	$message .= "\n";
	$message .= admin_url('post.php?post=' . $post_id . '&action=edit');
	
	wp_mail($author->user_email, $subject, $message);
	
	return true;
}
add_action('tm_post_rejected', 'tm_notify_rejected');
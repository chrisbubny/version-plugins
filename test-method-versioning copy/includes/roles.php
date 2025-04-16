<?php
/**
 * Handles role and capability management
 */

/**
 * Register custom roles
 */
function tm_register_roles() {
	// TP Contributor role
	add_role(
		'tp_contributor',
		__('TP Contributor', 'test-method-versioning'),
		array(
			'read' => true,
			'edit_posts' => true,
			'edit_test_method' => true,
			'create_test_method' => true,
			'submit_for_review' => true,
		)
	);

	// TP Approver role
	add_role(
		'tp_approver',
		__('TP Approver', 'test-method-versioning'),
		array(
			'read' => true,
			'edit_test_method' => true,
			'approve_test_method' => true,
			'review_test_method' => true,
		)
	);

	// TP Admin role
	add_role(
		'tp_admin',
		__('TP Admin', 'test-method-versioning'),
		array(
			'read' => true,
			'edit_test_method' => true,
			'publish_test_method' => true,
			'approve_test_method' => true,
			'review_test_method' => true,
			'unlock_test_method' => true,
			'create_version' => true,
		)
	);
}

/**
 * Add capabilities to existing roles
 */
function tm_add_caps_to_roles() {
	// Add capabilities to administrator
	$admin = get_role('administrator');
	
	if ($admin) {
		$admin->add_cap('edit_test_method');
		$admin->add_cap('create_test_method');
		$admin->add_cap('publish_test_method');
		$admin->add_cap('approve_test_method');
		$admin->add_cap('review_test_method');
		$admin->add_cap('unlock_test_method');
		$admin->add_cap('create_version');
		$admin->add_cap('submit_for_review');
	}
}

/**
 * Remove roles on plugin deactivation
 */
function tm_remove_roles() {
	remove_role('tp_contributor');
	remove_role('tp_approver');
	remove_role('tp_admin');
}
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
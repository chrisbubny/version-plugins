<?php
/**
 * Registers post types
 */
function tm_register_post_types() {
	$labels = array(
		'name'                  => _x('Test Methods', 'Post type general name', 'test-method-versioning'),
		'singular_name'         => _x('Test Method', 'Post type singular name', 'test-method-versioning'),
		'menu_name'             => _x('Test Methods', 'Admin Menu text', 'test-method-versioning'),
		'name_admin_bar'        => _x('Test Method', 'Add New on Toolbar', 'test-method-versioning'),
		'add_new'               => __('Add New', 'test-method-versioning'),
		'add_new_item'          => __('Add New Test Method', 'test-method-versioning'),
		'new_item'              => __('New Test Method', 'test-method-versioning'),
		'edit_item'             => __('Edit Test Method', 'test-method-versioning'),
		'view_item'             => __('View Test Method', 'test-method-versioning'),
		'all_items'             => __('All Test Methods', 'test-method-versioning'),
		'search_items'          => __('Search Test Methods', 'test-method-versioning'),
		'parent_item_colon'     => __('Parent Test Methods:', 'test-method-versioning'),
		'not_found'             => __('No test methods found.', 'test-method-versioning'),
		'not_found_in_trash'    => __('No test methods found in Trash.', 'test-method-versioning'),
		'featured_image'        => _x('Test Method Cover Image', 'Overrides the "Featured Image" phrase', 'test-method-versioning'),
		'set_featured_image'    => _x('Set cover image', 'Overrides the "Set featured image" phrase', 'test-method-versioning'),
		'remove_featured_image' => _x('Remove cover image', 'Overrides the "Remove featured image" phrase', 'test-method-versioning'),
		'use_featured_image'    => _x('Use as cover image', 'Overrides the "Use as featured image" phrase', 'test-method-versioning'),
		'archives'              => _x('Test Method archives', 'The post type archive label used in nav menus', 'test-method-versioning'),
		'insert_into_item'      => _x('Insert into test method', 'Overrides the "Insert into post" phrase', 'test-method-versioning'),
		'uploaded_to_this_item' => _x('Uploaded to this test method', 'Overrides the "Uploaded to this post" phrase', 'test-method-versioning'),
		'filter_items_list'     => _x('Filter test methods list', 'Screen reader text for the filter links', 'test-method-versioning'),
		'items_list_navigation' => _x('Test Methods list navigation', 'Screen reader text for the pagination', 'test-method-versioning'),
		'items_list'            => _x('Test Methods list', 'Screen reader text for the items list', 'test-method-versioning'),
	);

	// Check if post type already exists
	if (!post_type_exists('test_method')) {
		$args = array(
			'labels'             => $labels,
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'query_var'          => true,
			'rewrite'            => array('slug' => 'test-method'),
			'capability_type'    => 'post',
			'map_meta_cap'       => true,
			'has_archive'        => true,
			'hierarchical'       => false,
			'menu_position'      => null,
			'supports'           => array('title', 'editor', 'author', 'thumbnail', 'excerpt', 'custom-fields', 'revisions'),
			'show_in_rest'       => true,
			'menu_icon'          => 'dashicons-clipboard',
		);

		register_post_type('test_method', $args);
	}
}
add_action('init', 'tm_register_post_types');

/**
 * Add custom capabilities to post type
 */
function tm_add_post_type_capabilities() {
	// Get roles that need capabilities
	$admin = get_role('administrator');
	$tp_admin = get_role('tp_admin');
	$tp_approver = get_role('tp_approver');
	$tp_contributor = get_role('tp_contributor');
	
	// Administrator caps
	if ($admin) {
		$admin->add_cap('edit_test_method');
		$admin->add_cap('read_test_method');
		$admin->add_cap('delete_test_method');
		$admin->add_cap('edit_test_methods');
		$admin->add_cap('edit_others_test_methods');
		$admin->add_cap('publish_test_methods');
		$admin->add_cap('read_private_test_methods');
		$admin->add_cap('delete_test_methods');
		$admin->add_cap('delete_private_test_methods');
		$admin->add_cap('delete_published_test_methods');
		$admin->add_cap('delete_others_test_methods');
		$admin->add_cap('edit_private_test_methods');
		$admin->add_cap('edit_published_test_methods');
	}
	
	// TP Admin caps
	if ($tp_admin) {
		$tp_admin->add_cap('edit_test_method');
		$tp_admin->add_cap('read_test_method');
		$tp_admin->add_cap('delete_test_method');
		$tp_admin->add_cap('edit_test_methods');
		$tp_admin->add_cap('edit_others_test_methods');
		$tp_admin->add_cap('publish_test_methods');
		$tp_admin->add_cap('read_private_test_methods');
		$tp_admin->add_cap('edit_private_test_methods');
		$tp_admin->add_cap('edit_published_test_methods');
	}
	
	// TP Approver caps
	if ($tp_approver) {
		$tp_approver->add_cap('edit_test_method');
		$tp_approver->add_cap('read_test_method');
		$tp_approver->add_cap('edit_test_methods');
		$tp_approver->add_cap('edit_others_test_methods');
		$tp_approver->add_cap('read_private_test_methods');
		$tp_approver->add_cap('edit_private_test_methods');
		$tp_approver->add_cap('edit_published_test_methods');
	}
	
	// TP Contributor caps
	if ($tp_contributor) {
		$tp_contributor->add_cap('edit_test_method');
		$tp_contributor->add_cap('read_test_method');
		$tp_contributor->add_cap('edit_test_methods');
		$tp_contributor->add_cap('edit_published_test_methods');
	}
}
add_action('admin_init', 'tm_add_post_type_capabilities');
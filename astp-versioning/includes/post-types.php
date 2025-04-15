<?php
/**
 * Register Custom Post Types
 *
 * @package ASTP_Versioning
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register Custom Post Types
 */
function astp_register_post_types() {
    // Test Method Post Type (Main content container)
    register_post_type('astp_test_method', [
        'labels' => [
            'name' => __('Test Methods', 'astp-versioning'),
            'singular_name' => __('Test Method', 'astp-versioning'),
            'add_new' => __('Add New', 'astp-versioning'),
            'add_new_item' => __('Add New Test Method', 'astp-versioning'),
            'edit_item' => __('Edit Test Method', 'astp-versioning'),
            'new_item' => __('New Test Method', 'astp-versioning'),
            'view_item' => __('View Test Method', 'astp-versioning'),
            'search_items' => __('Search Test Methods', 'astp-versioning'),
            'not_found' => __('No Test Methods found', 'astp-versioning'),
            'not_found_in_trash' => __('No Test Methods found in Trash', 'astp-versioning'),
            'menu_name' => __('Test Methods', 'astp-versioning'),
        ],
        'public' => true,
        'has_archive' => true,
        'supports' => ['title', 'editor', 'revisions', 'custom-fields', 'author', 'thumbnail'],
        'rewrite' => ['slug' => 'test-method'],
        'show_in_rest' => true, // Enable Gutenberg editor
        'capability_type' => 'post',
        'map_meta_cap' => true,
        'menu_icon' => 'dashicons-clipboard',
    ]);
    
    // Version Post Type (to store version metadata and snapshots)
    register_post_type('astp_version', [
        'labels' => [
            'name' => __('Versions', 'astp-versioning'),
            'singular_name' => __('Version', 'astp-versioning'),
            'add_new' => __('Add New', 'astp-versioning'),
            'add_new_item' => __('Add New Version', 'astp-versioning'),
            'edit_item' => __('Edit Version', 'astp-versioning'),
            'new_item' => __('New Version', 'astp-versioning'),
            'view_item' => __('View Version', 'astp-versioning'),
            'search_items' => __('Search Versions', 'astp-versioning'),
            'not_found' => __('No Versions found', 'astp-versioning'),
            'not_found_in_trash' => __('No Versions found in Trash', 'astp-versioning'),
            'menu_name' => __('Versions', 'astp-versioning'),
        ],
        'public' => false,
        'publicly_queryable' => false,
        'show_ui' => true,
        'show_in_menu' => false,
        'supports' => ['title', 'custom-fields', 'author'],
        'capability_type' => 'post',
        'map_meta_cap' => true,
        'menu_icon' => 'dashicons-backup',
    ]);
    
    // Changelog Post Type
    register_post_type('astp_changelog', [
        'labels' => [
            'name' => __('Change Logs', 'astp-versioning'),
            'singular_name' => __('Change Log', 'astp-versioning'),
            'add_new' => __('Add New', 'astp-versioning'),
            'add_new_item' => __('Add New Change Log', 'astp-versioning'),
            'edit_item' => __('Edit Change Log', 'astp-versioning'),
            'new_item' => __('New Change Log', 'astp-versioning'),
            'view_item' => __('View Change Log', 'astp-versioning'),
            'search_items' => __('Search Change Logs', 'astp-versioning'),
            'not_found' => __('No Change Logs found', 'astp-versioning'),
            'not_found_in_trash' => __('No Change Logs found in Trash', 'astp-versioning'),
            'menu_name' => __('Change Logs', 'astp-versioning'),
        ],
        'public' => true,
        'publicly_queryable' => true,
        'show_ui' => true,
        'show_in_menu' => false,
        'supports' => ['title', 'editor', 'custom-fields'],
        'rewrite' => ['slug' => 'changelog'],
        'show_in_rest' => true,
        'capability_type' => 'post',
        'map_meta_cap' => true,
        'menu_icon' => 'dashicons-list-view',
    ]);
}
add_action('init', 'astp_register_post_types');

/**
 * Register meta boxes for Test Methods
 */
function astp_register_test_method_meta_boxes() {
    add_meta_box(
        'astp_version_manager',
        __('Version Management', 'astp-versioning'),
        'astp_render_version_manager_metabox', // Function implemented in admin-ui.php
        'astp_test_method',
        'side',
        'high'
    );
}
add_action('add_meta_boxes', 'astp_register_test_method_meta_boxes'); 
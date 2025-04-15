<?php
/**
 * Register Custom Taxonomies
 *
 * @package ASTP_Versioning
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register Custom Taxonomies
 */
function astp_register_taxonomies() {
    // Change Type Taxonomy (Amendment, Addition, Removal)
    register_taxonomy('change_type', ['astp_changelog'], [
        'labels' => [
            'name' => __('Change Types', 'astp-versioning'),
            'singular_name' => __('Change Type', 'astp-versioning'),
            'search_items' => __('Search Change Types', 'astp-versioning'),
            'all_items' => __('All Change Types', 'astp-versioning'),
            'edit_item' => __('Edit Change Type', 'astp-versioning'),
            'update_item' => __('Update Change Type', 'astp-versioning'),
            'add_new_item' => __('Add New Change Type', 'astp-versioning'),
            'new_item_name' => __('New Change Type Name', 'astp-versioning'),
            'menu_name' => __('Change Types', 'astp-versioning'),
        ],
        'hierarchical' => true,
        'show_admin_column' => true,
        'show_in_rest' => true,
        'rewrite' => ['slug' => 'change-type'],
    ]);
    
    // Version Type Taxonomy (Major, Minor, Hotfix)
    register_taxonomy('version_type', ['astp_version'], [
        'labels' => [
            'name' => __('Version Types', 'astp-versioning'),
            'singular_name' => __('Version Type', 'astp-versioning'),
            'search_items' => __('Search Version Types', 'astp-versioning'),
            'all_items' => __('All Version Types', 'astp-versioning'),
            'edit_item' => __('Edit Version Type', 'astp-versioning'),
            'update_item' => __('Update Version Type', 'astp-versioning'),
            'add_new_item' => __('Add New Version Type', 'astp-versioning'),
            'new_item_name' => __('New Version Type Name', 'astp-versioning'),
            'menu_name' => __('Version Types', 'astp-versioning'),
        ],
        'hierarchical' => true,
        'show_admin_column' => true,
        'show_in_rest' => true,
        'rewrite' => ['slug' => 'version-type'],
    ]);
    
    // Test Method Section Taxonomy
    register_taxonomy('test_method_section', ['astp_test_method'], [
        'labels' => [
            'name' => __('Document Sections', 'astp-versioning'),
            'singular_name' => __('Document Section', 'astp-versioning'),
            'search_items' => __('Search Document Sections', 'astp-versioning'),
            'all_items' => __('All Document Sections', 'astp-versioning'),
            'edit_item' => __('Edit Document Section', 'astp-versioning'),
            'update_item' => __('Update Document Section', 'astp-versioning'),
            'add_new_item' => __('Add New Document Section', 'astp-versioning'),
            'new_item_name' => __('New Document Section Name', 'astp-versioning'),
            'menu_name' => __('Document Sections', 'astp-versioning'),
        ],
        'hierarchical' => true,
        'show_admin_column' => true,
        'show_in_rest' => true,
        'rewrite' => ['slug' => 'document-section'],
    ]);
    
    // Version Status Taxonomy (Published, In Development, Deprecated)
    register_taxonomy('version_status', ['astp_version'], [
        'labels' => [
            'name' => __('Version Statuses', 'astp-versioning'),
            'singular_name' => __('Version Status', 'astp-versioning'),
            'search_items' => __('Search Version Statuses', 'astp-versioning'),
            'all_items' => __('All Version Statuses', 'astp-versioning'),
            'edit_item' => __('Edit Version Status', 'astp-versioning'),
            'update_item' => __('Update Version Status', 'astp-versioning'),
            'add_new_item' => __('Add New Version Status', 'astp-versioning'),
            'new_item_name' => __('New Version Status Name', 'astp-versioning'),
            'menu_name' => __('Version Statuses', 'astp-versioning'),
        ],
        'hierarchical' => true,
        'show_admin_column' => true,
        'show_in_rest' => true,
        'rewrite' => ['slug' => 'version-status'],
    ]);
    
    // Document Type Taxonomy (CCG, TP)
    register_taxonomy('document_type', ['astp_version', 'astp_changelog'], [
        'labels' => [
            'name' => __('Document Types', 'astp-versioning'),
            'singular_name' => __('Document Type', 'astp-versioning'),
            'search_items' => __('Search Document Types', 'astp-versioning'),
            'all_items' => __('All Document Types', 'astp-versioning'),
            'edit_item' => __('Edit Document Type', 'astp-versioning'),
            'update_item' => __('Update Document Type', 'astp-versioning'),
            'add_new_item' => __('Add New Document Type', 'astp-versioning'),
            'new_item_name' => __('New Document Type Name', 'astp-versioning'),
            'menu_name' => __('Document Types', 'astp-versioning'),
        ],
        'hierarchical' => true,
        'show_admin_column' => true,
        'show_in_rest' => true,
        'rewrite' => ['slug' => 'document-type'],
    ]);
    
    // Add default terms
    astp_add_default_taxonomy_terms();
}
add_action('init', 'astp_register_taxonomies');

/**
 * Add default taxonomy terms
 */
function astp_add_default_taxonomy_terms() {
    // Don't insert terms if they already exist
    $change_types = get_terms([
        'taxonomy' => 'change_type',
        'hide_empty' => false,
    ]);
    
    if (is_wp_error($change_types) || empty($change_types)) {
        // Add default change types
        wp_insert_term('Addition', 'change_type', ['slug' => 'addition']);
        wp_insert_term('Removal', 'change_type', ['slug' => 'removal']);
        wp_insert_term('Amendment', 'change_type', ['slug' => 'amendment']);
    }
    
    $version_types = get_terms([
        'taxonomy' => 'version_type',
        'hide_empty' => false,
    ]);
    
    if (is_wp_error($version_types) || empty($version_types)) {
        // Add default version types
        wp_insert_term('Major', 'version_type', ['slug' => 'major']);
        wp_insert_term('Minor', 'version_type', ['slug' => 'minor']);
        wp_insert_term('Hotfix', 'version_type', ['slug' => 'hotfix']);
    }
    
    $version_statuses = get_terms([
        'taxonomy' => 'version_status',
        'hide_empty' => false,
    ]);
    
    if (is_wp_error($version_statuses) || empty($version_statuses)) {
        // Add default version statuses
        wp_insert_term('Published', 'version_status', ['slug' => 'published']);
        wp_insert_term('In Development', 'version_status', ['slug' => 'in-development']);
        wp_insert_term('Deprecated', 'version_status', ['slug' => 'deprecated']);
    }
    
    $test_method_sections = get_terms([
        'taxonomy' => 'test_method_section',
        'hide_empty' => false,
    ]);
    
    if (is_wp_error($test_method_sections) || empty($test_method_sections)) {
        // Add default document sections
        wp_insert_term('Regulation Text', 'test_method_section', ['slug' => 'regulation-text']);
        wp_insert_term('Standards Referenced', 'test_method_section', ['slug' => 'standards-referenced']);
        wp_insert_term('Required Update Deadlines', 'test_method_section', ['slug' => 'required-update-deadlines']);
        wp_insert_term('Certification Dependencies', 'test_method_section', ['slug' => 'certification-dependencies']);
        wp_insert_term('Technical Explanations', 'test_method_section', ['slug' => 'technical-explanations']);
        wp_insert_term('Revision History', 'test_method_section', ['slug' => 'revision-history']);
    }
    
    $document_types = get_terms([
        'taxonomy' => 'document_type',
        'hide_empty' => false,
    ]);
    
    if (is_wp_error($document_types) || empty($document_types)) {
        // Add default document types
        wp_insert_term('Certification Companion Guide', 'document_type', ['slug' => 'ccg']);
        wp_insert_term('Test Procedure', 'document_type', ['slug' => 'tp']);
    }
} 
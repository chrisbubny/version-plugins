<?php
/**
 * ASTP Versioning Uninstall
 *
 * Clean up plugin data when uninstalled
 *
 * @package ASTP_Versioning
 */

// If uninstall not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Delete options
$options = array(
    'astp_github_token',
    'astp_github_username',
    'astp_github_repository',
    'astp_default_document_header',
    'astp_default_document_footer',
    'astp_versioning_settings'
);

foreach ($options as $option) {
    delete_option($option);
}

// Handle post types
if (get_option('astp_remove_data_on_uninstall', false)) {
    global $wpdb;
    
    // Define post types
    $post_types = array(
        'astp_test_method',
        'astp_version',
        'astp_changelog'
    );
    
    // Get all posts from our post types
    $items = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT ID FROM {$wpdb->posts} WHERE post_type IN ('%s','%s','%s')",
            $post_types
        )
    );
    
    if ($items) {
        // Iterate through each post and delete it completely
        foreach ($items as $item) {
            wp_delete_post($item->ID, true);
        }
    }
    
    // Define taxonomies
    $taxonomies = array(
        'change_type',
        'version_type',
        'test_method_section',
        'version_status'
    );
    
    // Remove taxonomies
    foreach ($taxonomies as $taxonomy) {
        $terms = get_terms(array(
            'taxonomy' => $taxonomy,
            'hide_empty' => false
        ));
        
        if (!empty($terms) && !is_wp_error($terms)) {
            foreach ($terms as $term) {
                wp_delete_term($term->term_id, $taxonomy);
            }
        }
    }
    
    // Delete document directories and files
    $upload_dir = wp_upload_dir();
    $documents_dir = $upload_dir['basedir'] . '/astp-documents';
    
    // Recursively delete the document directory
    if (file_exists($documents_dir)) {
        astp_recursive_rmdir($documents_dir);
    }
}

/**
 * Recursively remove a directory
 *
 * @param string $dir Directory path
 * @return bool Whether the operation was successful
 */
function astp_recursive_rmdir($dir) {
    if (!is_dir($dir)) {
        return false;
    }
    
    $files = array_diff(scandir($dir), array('.', '..'));
    
    foreach ($files as $file) {
        $path = $dir . '/' . $file;
        
        if (is_dir($path)) {
            astp_recursive_rmdir($path);
        } else {
            unlink($path);
        }
    }
    
    return rmdir($dir);
} 
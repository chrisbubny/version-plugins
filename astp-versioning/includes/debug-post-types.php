<?php
/**
 * Debug Post Types
 * 
 * This file helps diagnose issues with post types showing in admin UI.
 * 
 * @package ASTP_Versioning
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Check if post types are registered and log results
 */
function astp_debug_post_types() {
    // List of post types to check (add any others you want to check)
    $post_types_to_check = array(
        'astp_ccg',
        'astp_tp',
        'astp_test_method',
        'astp_version',
        'astp_changelog'
    );
    
    // Create a log message
    $log_message = "=== POST TYPE REGISTRATION CHECK ===\n";
    $log_message .= "Time: " . date('Y-m-d H:i:s') . "\n";
    
    // Check each post type
    foreach ($post_types_to_check as $post_type) {
        $is_registered = post_type_exists($post_type);
        $log_message .= sprintf(
            "Post Type: %s - %s\n",
            $post_type,
            $is_registered ? "REGISTERED" : "NOT REGISTERED"
        );
    }
    
    // Add a line about WP registered post types
    $log_message .= "\nAll registered post types in WordPress:\n";
    $registered_types = get_post_types(array(), 'names');
    $log_message .= implode(", ", $registered_types);
    $log_message .= "\n\n";
    
    // Log to error log
    error_log($log_message);
    
    // Also check if the admin menu items are being added for post types
    // that don't actually exist
    add_action('admin_menu', 'astp_debug_admin_menu', 9999);
}

/**
 * Debug admin menu items to see what's being added
 */
function astp_debug_admin_menu() {
    global $menu, $submenu;
    
    $log_message = "=== ADMIN MENU ITEMS CHECK ===\n";
    
    // Check top level menu items
    $log_message .= "Top level menu items:\n";
    foreach ($menu as $item) {
        if (isset($item[2]) && strpos($item[2], 'edit.php?post_type=astp_') !== false) {
            $log_message .= "- " . $item[0] . " (" . $item[2] . ")\n";
        }
    }
    
    // Check submenu items
    $log_message .= "\nSubmenu items:\n";
    foreach ($submenu as $parent => $items) {
        if (strpos($parent, 'edit.php?post_type=astp_') !== false) {
            $log_message .= "Parent: " . $parent . "\n";
            foreach ($items as $item) {
                $log_message .= "  - " . $item[0] . " (" . $item[2] . ")\n";
            }
        }
    }
    
    // Log to error log
    error_log($log_message);
}

/**
 * Check for any posts in the database with the old post types
 */
function astp_debug_post_type_database_entries() {
    global $wpdb;
    
    // Check for old post types in the database
    $old_post_types = array('astp_ccg', 'astp_tp');
    $log_message = "=== DATABASE POST ENTRIES CHECK ===\n";
    
    foreach ($old_post_types as $post_type) {
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = %s",
            $post_type
        ));
        
        $log_message .= sprintf(
            "Post Type: %s - %d posts in database\n",
            $post_type,
            $count
        );
        
        if ($count > 0) {
            // Get some example post IDs
            $examples = $wpdb->get_col($wpdb->prepare(
                "SELECT ID FROM {$wpdb->posts} WHERE post_type = %s LIMIT 5",
                $post_type
            ));
            
            $log_message .= "Example post IDs: " . implode(", ", $examples) . "\n";
        }
    }
    
    // Log to error log
    error_log($log_message);
}

// Hook our debug function to an early action
add_action('init', 'astp_debug_post_types', 9999);

// Hook database check to admin_init to make sure DB is fully loaded
add_action('admin_init', 'astp_debug_post_type_database_entries'); 
<?php
/**
 * Unregister Old Post Types
 * 
 * This file explicitly unregisters old post types that are no longer used.
 * 
 * @package ASTP_Versioning
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Unregister old post types to clean up admin UI
 */
function astp_unregister_old_post_types() {
    // Check if the post types exist before trying to unregister
    if (post_type_exists('astp_ccg')) {
        unregister_post_type('astp_ccg');
        error_log('Unregistered astp_ccg post type');
    }
    
    if (post_type_exists('astp_tp')) {
        unregister_post_type('astp_tp');
        error_log('Unregistered astp_tp post type');
    }
}

// Hook late in init to ensure post types are registered first
add_action('init', 'astp_unregister_old_post_types', 20);

/**
 * Remove admin menu for old post types
 * This is a fallback in case unregister doesn't work
 */
function astp_remove_old_post_type_menu() {
    // Remove old post type menu items
    remove_menu_page('edit.php?post_type=astp_ccg');
    remove_menu_page('edit.php?post_type=astp_tp');
    
    error_log('Attempted to remove old post type menus');
}

// Run after admin menu is set up
add_action('admin_menu', 'astp_remove_old_post_type_menu', 999); 
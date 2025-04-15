<?php
/**
 * GitHub Integration Fix
 * 
 * This file loads the enhanced GitHub integration to fix GitHub pushing issues.
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Make sure the debug file exists
if (file_exists(dirname(__FILE__) . '/includes/github-debug.php')) {
    // Load the GitHub debug/fix file
    require_once dirname(__FILE__) . '/includes/github-debug.php';
    
    // Add a filter to override the default GitHub push function
    if (!has_filter('pre_astp_push_to_github')) {
        add_filter('pre_astp_push_to_github', 'astp_use_enhanced_github_push', 10, 1);
    }
    
    // Add an action to modify the push_to_github function directly
    add_action('init', function() {
        // Add a custom hook to the GitHub integration in the versioning.php file
        add_filter('astp_push_to_github', 'astp_use_enhanced_github_push', 10, 1);
    });
    
    // Add an admin notice to indicate the fix is active
    add_action('admin_notices', function() {
        // Only show on version post type
        global $post;
        if (!$post || get_post_type($post) !== 'astp_version') {
            return;
        }
        
        echo '<div class="notice notice-success is-dismissible">';
        echo '<p><strong>GitHub Fix Active:</strong> The enhanced GitHub integration is active. This should fix GitHub pushing issues.</p>';
        echo '</div>';
    });
} 
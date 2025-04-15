<?php
/**
 * Roles and Capabilities Management
 *
 * @package TestMethodWorkflow
 */

// Exit if accessed directly
if (!defined('ABSPATH')) exit;

/**
 * Test Method roles and capabilities class
 */
class TestMethod_RolesCapabilities {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Setup roles and capabilities
        add_action('admin_init', array($this, 'setup_roles_capabilities'));
        
        // Filter user capabilities for workflow
        add_filter('user_has_cap', array($this, 'filter_user_capabilities'), 10, 4);
        
        // Map meta caps for test methods
        add_filter('map_meta_cap', array($this, 'map_test_method_meta_caps'), 10, 4);
    }
    
    /**
     * Setup custom roles and capabilities
     */
    public function setup_roles_capabilities() {
        // Add custom roles if they don't exist
        if (!get_role('tp_contributor')) {
            add_role('tp_contributor', 'TP Contributor', array(
                'read' => true,
            ));
        }
        
        if (!get_role('tp_approver')) {
            add_role('tp_approver', 'TP Approver', array(
                'read' => true,
            ));
        }
        
        if (!get_role('tp_admin')) {
            add_role('tp_admin', 'TP Admin', array(
                'read' => true,
            ));
        }
        
        // Get role objects
        $admin = get_role('administrator');
        $tp_admin = get_role('tp_admin');
        $tp_approver = get_role('tp_approver');
        $tp_contributor = get_role('tp_contributor');
        
        // Define capabilities for test methods
        $capabilities = array(
            // Post type capabilities
            'edit_test_method',
            'read_test_method',
            'delete_test_method',
            'edit_test_methods',
            'edit_others_test_methods',
            'publish_test_methods',
            'read_private_test_methods',
            'delete_test_methods',
            'delete_private_test_methods',
            'delete_published_test_methods',
            'delete_others_test_methods',
            'edit_private_test_methods',
            'edit_published_test_methods',
            
            // Workflow specific capabilities
            'approve_test_methods',
            'reject_test_methods',
            'lock_test_methods',
            'unlock_test_methods',
        );
        
        // Core WordPress capabilities needed for admin access
        $core_admin_capabilities = array(
            'read',             // Basic reading capability
            'edit_posts',       // Needed to access wp-admin
            'upload_files',     // Allow uploading files/images
            'level_1',          // Basic access level
            'level_0',          // Basic access level
            'moderate_comments' // Allow viewing/moderating comments
        );
        
        // Add all capabilities to admin
        if ($admin) {
            foreach ($capabilities as $cap) {
                $admin->add_cap($cap);
            }
        }
        
        // TP Admin capabilities (can do everything)
        if ($tp_admin) {
            // Add test method capabilities
            foreach ($capabilities as $cap) {
                $tp_admin->add_cap($cap);
            }
            
            // Add core WordPress capabilities
            foreach ($core_admin_capabilities as $cap) {
                $tp_admin->add_cap($cap);
            }
        }
        
        // TP Approver capabilities
        if ($tp_approver) {
            $tp_approver->add_cap('edit_test_methods');
            $tp_approver->add_cap('edit_test_method');
            $tp_approver->add_cap('read_test_method');
            $tp_approver->add_cap('delete_test_method');
            $tp_approver->add_cap('edit_published_test_methods');
            $tp_approver->add_cap('approve_test_methods');
            $tp_approver->add_cap('reject_test_methods');
            $tp_approver->add_cap('read_private_test_methods');
            
            // These critical capabilities allow approvers to work with others' posts
            $tp_approver->add_cap('edit_others_test_methods');
            $tp_approver->add_cap('read_private_test_methods');
            $tp_approver->add_cap('edit_private_test_methods');
            
            // Add core WordPress capabilities
            foreach ($core_admin_capabilities as $cap) {
                $tp_approver->add_cap($cap);
            }
        }
        
        // TP Contributor capabilities
        if ($tp_contributor) {
            // Basic editing capabilities
            $tp_contributor->add_cap('edit_test_methods');
            $tp_contributor->add_cap('edit_test_method');
            $tp_contributor->add_cap('read_test_method');
            $tp_contributor->add_cap('delete_test_method');
            $tp_contributor->add_cap('read_private_test_methods');
            
            // Allow them to edit their own posts, even when pending
            $tp_contributor->add_cap('edit_published_test_methods'); // Helps with pending posts
            
            // Specifically add capability to create new test methods
            $tp_contributor->add_cap('publish_test_methods'); // Needed to create new posts
            
            // ADD THIS LINE: Allow editing other users' test methods
            $tp_contributor->add_cap('edit_others_test_methods');
            
            // Add core WordPress capabilities
            foreach ($core_admin_capabilities as $cap) {
                $tp_contributor->add_cap($cap);
            }
        }
    }
    
    /**
     * Filter user capabilities for test method workflow
     */
    public function filter_user_capabilities($allcaps, $caps, $args, $user) {
        // Only proceed if we're checking edit_post capability
        if (!isset($args[0]) || !in_array($args[0], array('edit_post', 'delete_post'))) {
            return $allcaps;
        }
        
        // Only proceed if we have a post ID
        if (!isset($args[2])) {
            return $allcaps;
        }
        
        $post_id = $args[2];
        $post = get_post($post_id);
        
        // Only apply to test_method post type
        if (!$post || $post->post_type !== 'test_method') {
            return $allcaps;
        }
        
        // Get current workflow status and lock status
        $is_locked = get_post_meta($post_id, '_is_locked', true);
        $user_roles = (array) $user->roles;
        
        // Check if user is the author
        $is_author = ($post->post_author == $user->ID);
        
        // For TP Contributors, allow them to edit their own posts that aren't locked
       // For TP Contributors, allow them to edit their own posts that aren't locked
       // AND any draft posts regardless of author
       if (in_array('tp_contributor', $user_roles)) {
           // Check if post is in draft status
           $workflow_status = get_post_meta($post_id, '_workflow_status', true);
           
           if (($is_author && !$is_locked) || ($workflow_status === 'draft' && !$is_locked)) {
               foreach ($caps as $cap) {
                   $allcaps[$cap] = true;
               }
               
               // Also grant specific post cap
               $allcaps['edit_posts'] = true;
               $allcaps['edit_published_test_methods'] = true;
               $allcaps['edit_others_test_methods'] = true;
           }
       }
        
        // For TP Approvers, allow them to edit any test method for review purposes
        if (in_array('tp_approver', $user_roles) && !$is_locked) {
            foreach ($caps as $cap) {
                $allcaps[$cap] = true;
            }
            
            // Also grant specific post cap
            $allcaps['edit_others_test_methods'] = true;
            $allcaps['edit_published_test_methods'] = true;
        }
        
        // For locked posts, only TP Admin and Administrator can edit
        if ($is_locked) {
            if (array_intersect($user_roles, array('tp_admin', 'administrator'))) {
                foreach ($caps as $cap) {
                    $allcaps[$cap] = true;
                }
            } else {
                // Remove edit capability for locked posts for all other users
                foreach ($caps as $cap) {
                    $allcaps[$cap] = false;
                }
            }
        }
        
        return $allcaps;
    }
    
    /**
     * Map meta capabilities for test methods
     */
    public function map_test_method_meta_caps($caps, $cap, $user_id, $args) {
        // Only handle edit_post, read_post, and delete_post caps
        if (!in_array($cap, array('edit_post', 'read_post', 'delete_post'))) {
            return $caps;
        }
        
        // Only proceed if we have a post ID
        if (!isset($args[0])) {
            return $caps;
        }
        
        $post_id = $args[0];
        $post = get_post($post_id);
        
        // Only apply to test_method post type
        if (!$post || $post->post_type !== 'test_method') {
            return $caps;
        }
        
        // Check if post is locked
        $is_locked = get_post_meta($post_id, '_is_locked', true);
        
        // Check user's role
        $user = get_userdata($user_id);
        if (!$user) {
            return $caps;
        }
        
        $user_roles = (array) $user->roles;
        
        // For locked posts, only TP Admin and Administrator can edit
        if ($is_locked && $cap === 'edit_post') {
            if (array_intersect($user_roles, array('tp_admin', 'administrator'))) {
                return array('edit_test_methods');
            } else {
                return array('do_not_allow');
            }
        }
        
        // For TP Contributors, allow them to edit their own posts during workflow
        if (in_array('tp_contributor', $user_roles) && $post->post_author == $user_id && $cap === 'edit_post') {
            return array('edit_test_methods');
        }
        
        // For TP Approvers, allow them to edit any test method for review
        if (in_array('tp_approver', $user_roles) && $cap === 'edit_post') {
            return array('edit_test_methods');
        }
        
        return $caps;
    }
}
<?php
/**
 * Post Type Registration
 *
 * @package TestMethodWorkflow
 */

// Exit if accessed directly
if (!defined('ABSPATH')) exit;

/**
 * Test Method post type class
 */
class TestMethod_PostType {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Register post type
        add_action('init', array($this, 'register_post_type'));
        
        // Register meta fields
        add_action('init', array($this, 'register_meta_fields'));
        
        // Force refresh all revisions
        add_filter('wp_revisions_to_keep', array($this, 'keep_all_revisions'), 10, 2);
        
        // Add post meta to post status transitions
        add_action('transition_post_status', array($this, 'handle_status_transition'), 10, 3);
        
        // Modify admin display for custom statuses
        add_filter('display_post_states', array($this, 'display_custom_post_states'), 10, 2);
    }
    
    /**
     * Register the custom post type
     */
    public function register_post_type() {
        $labels = array(
            'name'               => _x('Test Methods', 'post type general name', 'test-method-workflow'),
            'singular_name'      => _x('Test Method', 'post type singular name', 'test-method-workflow'),
            'menu_name'          => _x('Test Methods', 'admin menu', 'test-method-workflow'),
            'name_admin_bar'     => _x('Test Method', 'add new on admin bar', 'test-method-workflow'),
            'add_new'            => _x('Add New', 'test method', 'test-method-workflow'),
            'add_new_item'       => __('Add New Test Method', 'test-method-workflow'),
            'new_item'           => __('New Test Method', 'test-method-workflow'),
            'edit_item'          => __('Edit Test Method', 'test-method-workflow'),
            'view_item'          => __('View Test Method', 'test-method-workflow'),
            'all_items'          => __('All Test Methods', 'test-method-workflow'),
            'search_items'       => __('Search Test Methods', 'test-method-workflow'),
            'parent_item_colon'  => __('Parent Test Methods:', 'test-method-workflow'),
            'not_found'          => __('No test methods found.', 'test-method-workflow'),
            'not_found_in_trash' => __('No test methods found in Trash.', 'test-method-workflow')
        );
        
        $args = array(
            'labels'              => $labels,
            'public'              => true,
            'publicly_queryable'  => true,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'query_var'           => true,
            'rewrite'             => array('slug' => 'test-method'),
            'capability_type'     => array('test_method', 'test_methods'),
            'map_meta_cap'        => true,
            'has_archive'         => true,
            'hierarchical'        => false,
            'menu_position'       => null,
            'supports'            => array(
                'title',
                'editor',
                'author',
                'revisions',
                'custom-fields',
                'thumbnail'
            ),
            // Gutenberg support
            'show_in_rest'        => true,
            'rest_base'           => 'test-methods',
            'rest_controller_class' => 'WP_REST_Posts_Controller',
            'menu_icon'           => 'dashicons-clipboard',
        );
        
        register_post_type('test_method', $args);
        
        // Register custom statuses
        $this->register_custom_statuses();
    }
    
    /**
     * Register custom post statuses
     */
    private function register_custom_statuses() {
        // Pending Review status
        register_post_status('pending_review', array(
            'label'                     => _x('Pending Review', 'test-method'),
            'public'                    => false,
            'exclude_from_search'       => true,
            'show_in_admin_all_list'    => true,
            'show_in_admin_status_list' => true,
            'label_count'               => _n_noop('Pending Review <span class="count">(%s)</span>', 'Pending Review <span class="count">(%s)</span>'),
        ));
        
        // Pending Final Approval status
        register_post_status('pending_final_approval', array(
            'label'                     => _x('Pending Final Approval', 'test-method'),
            'public'                    => false,
            'exclude_from_search'       => true,
            'show_in_admin_all_list'    => true,
            'show_in_admin_status_list' => true,
            'label_count'               => _n_noop('Pending Final Approval <span class="count">(%s)</span>', 'Pending Final Approval <span class="count">(%s)</span>'),
        ));
        
        // Approved status
        register_post_status('approved', array(
            'label'                     => _x('Approved', 'test-method'),
            'public'                    => false,
            'exclude_from_search'       => true,
            'show_in_admin_all_list'    => true,
            'show_in_admin_status_list' => true,
            'label_count'               => _n_noop('Approved <span class="count">(%s)</span>', 'Approved <span class="count">(%s)</span>'),
        ));
        
        // Rejected status
        register_post_status('rejected', array(
            'label'                     => _x('Rejected', 'test-method'),
            'public'                    => false,
            'exclude_from_search'       => true,
            'show_in_admin_all_list'    => true,
            'show_in_admin_status_list' => true,
            'label_count'               => _n_noop('Rejected <span class="count">(%s)</span>', 'Rejected <span class="count">(%s)</span>'),
        ));
        
        // Locked status
        register_post_status('locked', array(
            'label'                     => _x('Locked', 'test-method'),
            'public'                    => true,
            'exclude_from_search'       => false,
            'show_in_admin_all_list'    => true,
            'show_in_admin_status_list' => true,
            'label_count'               => _n_noop('Locked <span class="count">(%s)</span>', 'Locked <span class="count">(%s)</span>'),
        ));
    }
    
    /**
     * Register meta fields
     */
public function register_meta_fields() {     
        // Workflow status
        register_post_meta('test_method', '_workflow_status', array(
            'show_in_rest' => true,
            'single' => true,
            'type' => 'string',
            'auth_callback' => function() { 
                return current_user_can('edit_test_methods');
            },
            'default' => 'draft'
        ));
        
        // Approvals
        register_post_meta('test_method', '_approvals', array(
            'show_in_rest' => true,
            'single' => true,
            'type' => 'object',
            'auth_callback' => function() { 
                return current_user_can('edit_test_methods');
            }
        ));
        
        // Locked status
        register_post_meta('test_method', '_is_locked', array(
            'show_in_rest' => true,
            'single' => true,
            'type' => 'boolean',
            'auth_callback' => function() { 
                return current_user_can('edit_test_methods');
            },
            'default' => false
        ));
        
        // Awaiting final approval
        register_post_meta('test_method', '_awaiting_final_approval', array(
            'show_in_rest' => true,
            'single' => true,
            'type' => 'boolean',
            'auth_callback' => function() { 
                return current_user_can('edit_test_methods');
            },
            'default' => false
        ));
        
        // Revision parent
        register_post_meta('test_method', '_revision_parent', array(
            'show_in_rest' => true,
            'single' => true,
            'type' => 'integer',
            'auth_callback' => function() { 
                return current_user_can('edit_test_methods');
            }
        ));
        
        // Is revision
        register_post_meta('test_method', '_is_revision', array(
            'show_in_rest' => true,
            'single' => true,
            'type' => 'boolean',
            'auth_callback' => function() { 
                return current_user_can('edit_test_methods');
            },
            'default' => false
        ));
        
        // Revision history
        register_post_meta('test_method', '_revision_history', array(
            'show_in_rest' => true,
            'single' => true,
            'type' => 'object',
            'auth_callback' => function() { 
                return current_user_can('edit_test_methods');
            }
        ));
        
     // Version tracking - changed default from '0.1' to '0.0'
         register_post_meta('test_method', '_current_version_number', array(
             'show_in_rest' => true,
             'single' => true,
             'type' => 'string',
             'auth_callback' => function() { 
                 return current_user_can('edit_test_methods');
             },
             'default' => '0.0'
         ));
        
        // Version change type (major, minor, none)
        register_post_meta('test_method', '_cpt_version', array(
            'show_in_rest' => true,
            'single' => true,
            'type' => 'string',
            'auth_callback' => function() { 
                return current_user_can('edit_test_methods');
            },
            'default' => 'no_change'
        ));
        
        // Version note
        register_post_meta('test_method', '_cpt_version_note', array(
            'show_in_rest' => true,
            'single' => true,
            'type' => 'string',
            'auth_callback' => function() { 
                return current_user_can('edit_test_methods');
            }
        ));
    }
    
    /**
     * Keep all revisions for test method post type
     */
    public function keep_all_revisions($num, $post) {
        if ($post->post_type === 'test_method') {
            return -1; // Keep all revisions
        }
        return $num;
    }
    
    /**
     * Handle post status transitions
     */
    public function handle_status_transition($new_status, $old_status, $post) {
        if ($post->post_type !== 'test_method') {
            return;
        }
        
        // When a post is published, automatically lock it
        if ($new_status === 'publish' && $old_status !== 'publish') {
            update_post_meta($post->ID, '_workflow_status', 'publish');
            update_post_meta($post->ID, '_is_locked', true);
            
            // Record in revision history
            $this->add_to_revision_history($post->ID, 'published and locked');
        }
    }
    
    /**
     * Add entry to revision history
     */
   private function add_to_revision_history($post_id, $status) {
       $revision_history = get_post_meta($post_id, '_revision_history', true);
       
       if (!is_array($revision_history)) {
           $revision_history = array();
       }
       
       // Get current version
       $current_version = get_post_meta($post_id, '_current_version_number', true);
       if (empty($current_version)) {
           $current_version = '0.1';
       }
       
       $revision_history[] = array(
           'version' => count($revision_history) + 1,
           'user_id' => get_current_user_id(),
           'date' => time(),
           'status' => $status,
           'version_number' => $current_version
       );
       
       update_post_meta($post_id, '_revision_history', $revision_history);
   }
    
    /**
     * Display custom post states in admin
     */
    public function display_custom_post_states($post_states, $post) {
        if ($post->post_type !== 'test_method') {
            return $post_states;
        }
        
        $workflow_status = get_post_meta($post->ID, '_workflow_status', true);
        $is_locked = get_post_meta($post->ID, '_is_locked', true);
        $is_revision = get_post_meta($post->ID, '_is_revision', true);
        
        // Add workflow status to post states
        if ($workflow_status && $workflow_status !== 'draft' && $workflow_status !== 'publish') {
            $status_label = ucfirst(str_replace('_', ' ', $workflow_status));
            $post_states['workflow_status'] = $status_label;
        }
        
        // Add locked state
        if ($is_locked) {
            $post_states['locked'] = __('Locked', 'test-method-workflow');
        }
        
        // Add revision state
        if ($is_revision) {
            $post_states['revision'] = __('Revision', 'test-method-workflow');
        }
        
        return $post_states;
    }
}
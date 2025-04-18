<?php
/**
 * Plugin Name: ASTP Versioning System
 * Description: A comprehensive versioning system for Test Methods with change tracking, document generation, and GitHub integration.
 * Version: 1.0.0
 * Author: ASTP Development Team
 * Text Domain: astp-versioning
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Set to true to enable block debug tools if blocks aren't appearing
if (!defined('ASTP_BLOCKS_DEBUG')) {
    define('ASTP_BLOCKS_DEBUG', false); // Turn off debug mode
}

// Define plugin constants
define('ASTP_VERSIONING_DIR', plugin_dir_path(__FILE__));
define('ASTP_VERSIONING_URL', plugin_dir_url(__FILE__));
define('ASTP_VERSIONING_VERSION', '1.0.0');
define('ASTP_PLUGIN_URL', plugin_dir_url(__FILE__));
define('ASTP_PLUGIN_DIR', plugin_dir_path(__FILE__));

/**
 * Main ASTP Versioning class
 */
class ASTP_Versioning {
    /**
     * Instance of this class
     */
    private static $instance = null;

    /**
     * Return an instance of this class
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self;
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        // Load plugin dependencies
        $this->load_dependencies();

        // Register hooks
        $this->register_hooks();
    }

    /**
     * Load required files and classes
     */
    private function load_dependencies() {
        // Load core files
        require_once ASTP_VERSIONING_DIR . 'includes/post-types.php';
        require_once ASTP_VERSIONING_DIR . 'includes/taxonomies.php';
        require_once ASTP_VERSIONING_DIR . 'includes/admin-ui.php';
        require_once ASTP_VERSIONING_DIR . 'includes/block-editor.php';
        require_once ASTP_VERSIONING_DIR . 'includes/frontend-ui.php';
        require_once ASTP_VERSIONING_DIR . 'includes/document-generation.php';
        require_once ASTP_VERSIONING_DIR . 'includes/versioning.php';
        require_once ASTP_VERSIONING_DIR . 'includes/github-integration.php';
        
        // Block registration
        require_once ASTP_VERSIONING_DIR . 'includes/block-setup.php';
        require_once ASTP_VERSIONING_DIR . 'includes/test-method-blocks.php';
        require_once ASTP_VERSIONING_DIR . 'includes/custom-blocks.php';
        require_once ASTP_VERSIONING_DIR . 'includes/changelog-render.php';
        
        // New revision system files
        require_once ASTP_VERSIONING_DIR . 'includes/class-astp-revision-post-type.php';
        require_once ASTP_VERSIONING_DIR . 'includes/class-astp-document-import.php';
        require_once ASTP_VERSIONING_DIR . 'includes/class-astp-revision-publishing.php';
        
        // Debug helpers
        if (defined('WP_DEBUG') && WP_DEBUG) {
            require_once ASTP_VERSIONING_DIR . 'includes/debug-helper.php';
            require_once ASTP_VERSIONING_DIR . 'includes/github-debug.php';
        }
        
        // Load any additional ACF fields if ACF is active
        if (class_exists('ACF')) {
            require_once ASTP_VERSIONING_DIR . 'includes/acf-fields.php';
        }
    }

    /**
     * Register all hooks for the plugin
     */
    private function register_hooks() {
        // Activation and deactivation hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));

        // Enqueue scripts and styles for admin
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        
        // Enqueue scripts and styles for frontend
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        
        // Register custom image sizes
        add_action('after_setup_theme', array($this, 'register_image_sizes'));
    }

    /**
     * Actions to perform on plugin activation
     */
    public function activate() {
        // Create required directory structure for document storage
        $this->create_document_storage_directories();
        
        // Register post types for activation
        if (function_exists('astp_register_post_types')) {
            astp_register_post_types();
        }
        
        // Register taxonomies for activation
        if (function_exists('astp_register_taxonomies')) {
            astp_register_taxonomies();
        }

        // Add default taxonomy terms
        if (function_exists('astp_add_default_taxonomy_terms')) {
            astp_add_default_taxonomy_terms();
        }
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Actions to perform on plugin deactivation
     */
    public function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Create document storage directories
     */
    private function create_document_storage_directories() {
        $upload_dir = wp_upload_dir();
        $documents_dir = $upload_dir['basedir'] . '/astp-documents';
        
        if (!file_exists($documents_dir)) {
            wp_mkdir_p($documents_dir);
        }
        
        $dirs = array(
            '/pdf',
            '/word',
            '/html',
            '/snapshots',
            '/test-method-revisions' // New directory for revision documents
        );
        
        foreach ($dirs as $dir) {
            if (!file_exists($documents_dir . $dir)) {
                wp_mkdir_p($documents_dir . $dir);
            }
        }
        
        // Create .htaccess to protect snapshots from direct access
        $htaccess_content = "deny from all";
        $htaccess_file = $documents_dir . '/snapshots/.htaccess';
        
        if (!file_exists($htaccess_file)) {
            file_put_contents($htaccess_file, $htaccess_content);
        }
    }

    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_assets($hook) {
        // Only load on relevant screens
        global $post_type;
        
        // Load on specific post types
        $post_types = array('test_method', 'astp_version', 'test_method_revision');
        
        // Check if we're on a relevant page
        $is_relevant_page = false;
        
        if (
            (in_array($post_type, $post_types) && in_array($hook, array('post.php', 'post-new.php'))) ||
            (strpos($hook, 'astp-versioning') !== false)
        ) {
            $is_relevant_page = true;
        }
        
        // Only load assets on relevant pages
        if ($is_relevant_page) {
            // NOTE: Admin script/styles are now loaded in admin-ui.php
            // We're keeping this function for compatibility and for loading block editor assets
        }
        
        // Block editor assets - only load on our specific post type editor screens
        if (($post_type === 'test_method' || $post_type === 'test_method_revision') && in_array($hook, array('post.php', 'post-new.php'))) {
            // Enqueue core editor dependencies
            wp_enqueue_script('wp-blocks');
            wp_enqueue_script('wp-element');
            wp_enqueue_script('wp-editor');
            wp_enqueue_script('wp-block-editor'); 
            wp_enqueue_script('wp-components');
            wp_enqueue_script('wp-i18n');
            wp_enqueue_script('wp-data');
            
            // Make sure our block scripts will be loaded
            do_action('enqueue_block_editor_assets');
            
            // Debug message
            if (WP_DEBUG) {
                error_log('ASTP Versioning: Loading block editor assets for ' . $post_type);
            }
        }
    }

    /**
     * Enqueue frontend scripts and styles
     */
    public function enqueue_frontend_assets() {
        global $post;
        
        if (!is_object($post)) {
            return;
        }
        
        // Only load on the relevant post types
        if (
            is_singular(array('test_method', 'astp_version')) ||
            has_shortcode($post->post_content, 'astp_version_info') ||
            has_shortcode($post->post_content, 'astp_version_history') ||
            has_shortcode($post->post_content, 'astp_version_changes') ||
            has_shortcode($post->post_content, 'astp_document_downloads')
        ) {
            // Frontend styles
            wp_enqueue_style(
                'astp-frontend',
                ASTP_VERSIONING_URL . 'assets/css/frontend.css',
                array(),
                ASTP_VERSIONING_VERSION
            );
            
            // Test Method blocks frontend styles
            wp_enqueue_style(
                'astp-test-method-blocks',
                ASTP_VERSIONING_URL . 'assets/css/test-method-blocks.css',
                array(),
                ASTP_VERSIONING_VERSION
            );
            
            // Frontend scripts
            wp_enqueue_script(
                'astp-frontend',
                ASTP_VERSIONING_URL . 'assets/js/frontend.js',
                array('jquery'),
                ASTP_VERSIONING_VERSION,
                true
            );
            
            // Localize script with data
            wp_localize_script('astp-frontend', 'astp_frontend', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('astp_frontend_nonce')
            ));
        }
    }

    /**
     * Register custom image sizes
     */
    public function register_image_sizes() {
        // Register image sizes for document generation
        add_image_size('astp-document-large', 1200, 0, false);
        add_image_size('astp-document-medium', 800, 0, false);
        add_image_size('astp-document-thumbnail', 300, 0, false);
    }
}

// Initialize the plugin
function astp_versioning_init() {
    return ASTP_Versioning::get_instance();
}

// Launch the plugin
astp_versioning_init();

/**
 * ASTP_Create_Revision Class
 * This handles the AJAX for creating revisions from test_methods
 */
class ASTP_Create_Revision {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('wp_ajax_astp_create_revision', array($this, 'ajax_create_revision'));
    }
    
    /**
     * AJAX handler for creating revisions
     */
    public function ajax_create_revision() {
        // Check nonce
        check_ajax_referer('astp_create_revision_nonce', 'nonce');
        
        // Check permissions
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'astp-versioning')));
        }
        
        $parent_id = isset($_POST['parent_id']) ? intval($_POST['parent_id']) : 0;
        $revision_type = isset($_POST['revision_type']) ? sanitize_text_field($_POST['revision_type']) : '';
        
        if (!$parent_id || !$revision_type) {
            wp_send_json_error(array('message' => __('Invalid parameters.', 'astp-versioning')));
        }
        
        // Get parent post
        $parent = get_post($parent_id);
        
        if (!$parent || 'test_method' !== $parent->post_type) {
            wp_send_json_error(array('message' => __('Invalid parent test method.', 'astp-versioning')));
        }
        
        // Create revision
        $revision_id = $this->create_revision($parent, $revision_type);
        
        if (is_wp_error($revision_id)) {
            wp_send_json_error(array('message' => $revision_id->get_error_message()));
        }
        
        wp_send_json_success(array(
            'message' => __('Revision created successfully.', 'astp-versioning'),
            'redirect' => get_edit_post_link($revision_id, 'url')
        ));
    }
    
    /**
     * Create revision post
     */
    private function create_revision($parent, $revision_type) {
        // Create new post
        $revision_id = wp_insert_post(array(
            'post_title' => sprintf(__('Revision of %s', 'astp-versioning'), $parent->post_title),
            'post_type' => 'test_method_revision',
            'post_status' => 'draft',
            'post_author' => get_current_user_id(),
        ), true);
        
        if (is_wp_error($revision_id)) {
            return $revision_id;
        }
        
        // Set post meta
        update_post_meta($revision_id, '_parent_test_method', $parent->ID);
        update_post_meta($revision_id, '_revision_type', $revision_type);
        
        // Set version types based on revision type
        if ('ccg' === $revision_type || 'both' === $revision_type) {
            update_post_meta($revision_id, '_ccg_version_type', 'minor');
        }
        
        if ('tp' === $revision_type || 'both' === $revision_type) {
            update_post_meta($revision_id, '_tp_version_type', 'minor');
        }
        
        return $revision_id;
    }
}

// Initialize the class
new ASTP_Create_Revision();

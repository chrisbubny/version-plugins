<?php
/**
 * Custom Blocks Registration and Loading
 *
 * @package ASTP_Versioning
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register custom blocks and scripts
 */
function astp_register_custom_blocks() {
    // Skip block registration if Gutenberg is not available.
    if ( ! function_exists( 'register_block_type' ) ) {
        return;
    }

    // Define all blocks and their dependencies
    $blocks = array(
        // CCG blocks
        'clarification-block' => array(
            'category' => 'astp-ccg',
            'deps' => array('clarification-item')
        ),
        'regulatory-block' => array(
            'category' => 'astp-ccg',
            'deps' => array('regulatory-section')
        ),
        'standards-block' => array(
            'category' => 'astp-ccg'
        ),
        'dependencies-block' => array(
            'category' => 'astp-ccg'
        ),
        'ccg-editor-wrapper' => array(
            'category' => 'layout'
        ),
        
        // TP blocks
        'resources-block' => array(
            'category' => 'astp-tp'
        ),
        'testing-component-block' => array(
            'category' => 'astp-tp'
        ),
        'testing-steps-block' => array(
            'category' => 'astp-tp',
            'deps' => array('testing-steps-item', 'testing-steps-accordion', 'testing-steps-section', 'testing-steps-tools')
        ),
        'testing-tools-block' => array(
            'category' => 'astp-tp'
        ),
        
        // Common blocks
        'changelog-block' => array(
            'category' => 'common'
        )
    );
    
    // Child components that don't need their own block registration
    $components = array(
        'clarification-item',
        'regulatory-section',
        'testing-steps-item',
        'testing-steps-accordion',
        'testing-steps-section',
        'testing-steps-tools'
    );
    
    // Core WordPress dependencies for all blocks
    $core_deps = array('wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-data', 'wp-block-editor', 'wp-i18n');
    
    // Register component scripts first
    foreach ($components as $component) {
        $script_path = "custom-blocks/uswds-{$component}.js";
        $script_url = ASTP_VERSIONING_URL . $script_path;
        $script_dir = ASTP_VERSIONING_DIR . $script_path;
        
        if (file_exists($script_dir)) {
            wp_register_script(
                "astp-{$component}", 
                $script_url,
                $core_deps,
                filemtime($script_dir),
                true
            );
        }
    }
    
    // Register and enqueue block scripts
    foreach ($blocks as $block => $config) {
        $script_handle = "astp-{$block}";
        $script_path = "custom-blocks/uswds-{$block}.js";
        $script_url = ASTP_VERSIONING_URL . $script_path;
        $script_dir = ASTP_VERSIONING_DIR . $script_path;
        
        // Define dependencies including core and any child components
        $dependencies = $core_deps;
        if (isset($config['deps']) && is_array($config['deps'])) {
            foreach ($config['deps'] as $dep) {
                $dependencies[] = "astp-{$dep}";
            }
        }
        
        // Register the script
        if (file_exists($script_dir)) {
            wp_register_script(
                $script_handle,
                $script_url,
                $dependencies,
                filemtime($script_dir),
                true
            );
            
            // Register the block type
            register_block_type( "uswds-gutenberg/{$block}", array(
                'editor_script' => $script_handle,
                'category' => $config['category'],
            ) );
        }
    }
    
    // Add custom block category for all USWDS blocks
    add_filter( 'block_categories_all', 'astp_register_block_categories', 10, 2 );
    
    if ( WP_DEBUG ) {
        error_log( 'Registered USWDS custom blocks' );
    }
}
add_action( 'init', 'astp_register_custom_blocks', 99 );

/**
 * Register custom block categories for CCG and TP
 *
 * @param array $categories Existing block categories
 * @param WP_Post $post The current post
 * @return array Updated block categories
 */
function astp_register_block_categories( $categories, $post ) {
    return array_merge(
        $categories,
        array(
            array(
                'slug' => 'astp-ccg',
                'title' => __( 'Certification Companion Guide', 'astp-versioning' ),
                'icon'  => 'book-alt',
            ),
            array(
                'slug' => 'astp-tp',
                'title' => __( 'Test Procedure', 'astp-versioning' ),
                'icon'  => 'clipboard',
            ),
        )
    );
}

/**
 * Create the unified JavaScript file that loads all custom block scripts.
 * Only generates the file if it doesn't already exist.
 */
function astp_enqueue_custom_blocks() {
    $js_file_path = ASTP_VERSIONING_DIR . 'custom-blocks/uswds-blocks.js';
    
    // Create the custom-blocks directory if it doesn't exist
    if ( ! file_exists( dirname( $js_file_path ) ) ) {
        wp_mkdir_p( dirname( $js_file_path ) );
    }
    
    // Only generate the file if it doesn't exist
    if ( ! file_exists( $js_file_path ) ) {
        $js_content = "/**\n";
        $js_content .= " * This file is auto-generated and serves as the entry point for all custom blocks.\n";
        $js_content .= " * DO NOT EDIT DIRECTLY\n";
        $js_content .= " */\n\n";
        
        // Add imports for each block script
        $blocks = array(
            'uswds-clarification-item',
            'uswds-clarifications-block',
            'uswds-dependencies-block',
            'uswds-regulatory-block',
            'uswds-regulatory-section',
            'uswds-resources-block',
            'uswds-standards-block',
            'uswds-testing-component-block',
            'uswds-testing-steps-accordion',
            'uswds-testing-steps-block',
            'uswds-testing-steps-item',
            'uswds-testing-steps-section',
            'uswds-testing-steps-tools',
            'uswds-testing-tools-block',
            'uswds-changelog-block'
        );
        
        foreach ( $blocks as $block ) {
            $js_content .= "import './{$block}.js';\n";
        }
        
        file_put_contents( $js_file_path, $js_content );
        
        if ( WP_DEBUG ) {
            error_log( 'Generated USWDS blocks entry point file' );
        }
    }
}
add_action( 'admin_init', 'astp_enqueue_custom_blocks' );

/**
 * Include required custom block files
 */
function astp_include_custom_block_files() {
    // Include the changelog block renderer
    require_once ASTP_VERSIONING_DIR . 'includes/changelog-render.php';
    
    if (WP_DEBUG) {
        error_log('Included custom block files');
    }
}
add_action('plugins_loaded', 'astp_include_custom_block_files');

/**
 * Enqueue scripts for the block editor
 */
function astp_enqueue_block_editor_assets() {
    // For blocks, we rely on the registration that happens in astp_register_custom_blocks
    // No need to enqueue scripts here as they are already registered and associated with blocks
    
    // Debug mode script only if enabled
    if (defined('ASTP_BLOCKS_DEBUG') && ASTP_BLOCKS_DEBUG) {
        $debug_script = ASTP_VERSIONING_DIR . 'custom-blocks/uswds-blocks-debug.js';
        if (file_exists($debug_script)) {
            wp_enqueue_script(
                'uswds-blocks-debug',
                ASTP_VERSIONING_URL . 'custom-blocks/uswds-blocks-debug.js',
                array('wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-data', 'wp-block-editor', 'wp-i18n'),
                filemtime($debug_script),
                true
            );
            
            if (WP_DEBUG) {
                error_log('Enqueued USWDS blocks debug script');
            }
        }
    }
    
    if (WP_DEBUG) {
        error_log('Block editor assets hook fired');
    }
}
add_action('enqueue_block_editor_assets', 'astp_enqueue_block_editor_assets');

// Make sure to enqueue the block assets specifically in the block editor on our post types
function astp_ensure_block_assets_loaded($hook) {
    global $post_type;
    
    // Turn on debug mode temporarily when editing our custom post types
    if ($post_type === 'astp_test_method' && in_array($hook, array('post.php', 'post-new.php'))) {
        // Force all blocks to get registered again if needed
        if (!defined('ASTP_BLOCKS_DEBUG')) {
            define('ASTP_BLOCKS_DEBUG', true);
        }
        
        // Make sure the debug script is loaded to catch any potential issues
        $debug_script = ASTP_VERSIONING_DIR . 'custom-blocks/uswds-blocks-debug.js';
        if (file_exists($debug_script)) {
            wp_enqueue_script(
                'uswds-blocks-debug-forced',
                ASTP_VERSIONING_URL . 'custom-blocks/uswds-blocks-debug.js',
                array('wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-data', 'wp-block-editor', 'wp-i18n'),
                filemtime($debug_script),
                true
            );
        }
        
        if (WP_DEBUG) {
            error_log('Explicitly loaded block editor debug assets for ' . $post_type);
        }
    }
}
add_action('admin_enqueue_scripts', 'astp_ensure_block_assets_loaded'); 
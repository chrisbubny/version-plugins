<?php
/**
 * Block Editor Integration
 *
 * @package ASTP_Versioning
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register editor assets for version management
 */
function astp_register_block_editor_assets() {
    // Only register for our post type
    $screen = get_current_screen();
    if (!$screen || $screen->post_type !== 'astp_test_method') {
        return;
    }
    
    // Enqueue block editor scripts
    wp_enqueue_script(
        'astp-block-editor-js',
        ASTP_VERSIONING_URL . 'assets/js/block-editor.js',
        ['wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-data', 'wp-compose', 'jquery'],
        ASTP_VERSIONING_VERSION,
        true
    );
    
    // Enqueue block editor styles
    wp_enqueue_style(
        'astp-block-editor-css',
        ASTP_VERSIONING_URL . 'assets/css/block-editor.css',
        ['wp-edit-blocks'],
        ASTP_VERSIONING_VERSION
    );
    
    // Get post ID
    $post_id = isset($_GET['post']) ? intval($_GET['post']) : 0;
    
    // Get version info if this is a version post
    $in_dev_version = false;
    if ($post_id) {
        $in_dev_version = get_post_meta($post_id, 'in_development_version', true);
    }
    
    // Pass data to script
    wp_localize_script('astp-block-editor-js', 'astp_editor', [
        'post_id' => $post_id,
        'in_dev_version' => $in_dev_version,
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('astp_editor_nonce'),
        'strings' => [
            'version_warning' => __('You are editing content that has a published version. Changes will not affect the published version until you create and publish a new version.', 'astp-versioning'),
            'version_editing' => __('You are editing a development version. Click "Publish Version" when ready to make it the current version.', 'astp-versioning'),
            'create_version' => __('Create New Version', 'astp-versioning'),
            'publish_version' => __('Publish Version', 'astp-versioning'),
            'version_exists' => __('A development version already exists', 'astp-versioning'),
        ],
    ]);
}
add_action('enqueue_block_editor_assets', 'astp_register_block_editor_assets');

/**
 * Register custom blocks for version information
 */
function astp_register_version_blocks() {
    // Check if Gutenberg is active
    if (!function_exists('register_block_type')) {
        return;
    }
    
    // Register version info block
    register_block_type('astp/version-info', [
        'attributes' => [
            'className' => [
                'type' => 'string',
                'default' => '',
            ],
            'showDownloads' => [
                'type' => 'boolean',
                'default' => true,
            ],
        ],
        'render_callback' => 'astp_render_version_info_block',
    ]);
    
    // Register version history block
    register_block_type('astp/version-history', [
        'attributes' => [
            'className' => [
                'type' => 'string',
                'default' => '',
            ],
            'limit' => [
                'type' => 'number',
                'default' => 5,
            ],
            'showDownloads' => [
                'type' => 'boolean',
                'default' => true,
            ],
        ],
        'render_callback' => 'astp_render_version_history_block',
    ]);
    
    // Register version changes block
    register_block_type('astp/version-changes', [
        'attributes' => [
            'className' => [
                'type' => 'string',
                'default' => '',
            ],
            'fromVersion' => [
                'type' => 'string',
                'default' => '',
            ],
            'toVersion' => [
                'type' => 'string',
                'default' => '',
            ],
            'format' => [
                'type' => 'string',
                'default' => 'detailed',
            ],
        ],
        'render_callback' => 'astp_render_version_changes_block',
    ]);
}
add_action('init', 'astp_register_version_blocks');

/**
 * Render version info block
 *
 * @param array $attributes Block attributes
 * @return string Block content
 */
function astp_render_version_info_block($attributes) {
    $class_name = isset($attributes['className']) ? $attributes['className'] : '';
    $show_downloads = isset($attributes['showDownloads']) ? $attributes['showDownloads'] : true;
    
    // Get post ID
    global $post;
    $post_id = isset($post->ID) ? $post->ID : 0;
    
    // Get version info HTML
    $output = astp_display_version_info($post_id, $show_downloads);
    
    // Add custom class if specified
    if (!empty($class_name)) {
        $output = str_replace('class="astp-version-info"', 'class="astp-version-info ' . esc_attr($class_name) . '"', $output);
    }
    
    return $output;
}

/**
 * Render version history block
 *
 * @param array $attributes Block attributes
 * @return string Block content
 */
function astp_render_version_history_block($attributes) {
    $class_name = isset($attributes['className']) ? $attributes['className'] : '';
    $limit = isset($attributes['limit']) ? intval($attributes['limit']) : 5;
    $show_downloads = isset($attributes['showDownloads']) ? $attributes['showDownloads'] : true;
    
    // Get post ID
    global $post;
    $post_id = isset($post->ID) ? $post->ID : 0;
    
    // Get version history HTML
    $output = astp_display_version_history($post_id, $limit, $show_downloads);
    
    // Add custom class if specified
    if (!empty($class_name)) {
        $output = str_replace('class="astp-version-history"', 'class="astp-version-history ' . esc_attr($class_name) . '"', $output);
    }
    
    return $output;
}

/**
 * Render version changes block
 *
 * @param array $attributes Block attributes
 * @return string Block content
 */
function astp_render_version_changes_block($attributes) {
    $class_name = isset($attributes['className']) ? $attributes['className'] : '';
    $from_version = isset($attributes['fromVersion']) ? $attributes['fromVersion'] : '';
    $to_version = isset($attributes['toVersion']) ? $attributes['toVersion'] : '';
    $format = isset($attributes['format']) ? $attributes['format'] : 'detailed';
    
    // Require both version IDs
    if (empty($from_version) || empty($to_version)) {
        return '<div class="components-notice is-warning"><p>' . __('Please select both from and to versions in the block settings.', 'astp-versioning') . '</p></div>';
    }
    
    // Get changes
    $changes = astp_get_version_changes($from_version, $to_version);
    $output = astp_render_changes_html($changes, $format);
    
    // Add custom class if specified
    if (!empty($class_name)) {
        $output = str_replace('class="astp-changes-display"', 'class="astp-changes-display ' . esc_attr($class_name) . '"', $output);
    }
    
    return $output;
}

/**
 * Save version post when parent post is updated
 *
 * @param int     $post_id Post ID
 * @param WP_Post $post    Post object
 */
function astp_check_for_version_updates($post_id, $post) {
    // Only for our post type
    if ($post->post_type !== 'astp_test_method') {
        return;
    }
    
    // Get in-development version
    $dev_version_id = get_post_meta($post_id, 'in_development_version', true);
    
    // If no development version, nothing to do
    if (!$dev_version_id) {
        return;
    }
    
    // Get post blocks
    $blocks = parse_blocks($post->post_content);
    
    // Store snapshot in version post
    update_post_meta($dev_version_id, 'content_snapshot', $blocks);
    
    // Update last modified date
    update_post_meta($dev_version_id, 'last_modified', current_time('mysql'));
}
add_action('wp_after_insert_post', 'astp_check_for_version_updates', 10, 2);

/**
 * AJAX handler for checking version status
 */
function astp_ajax_check_version_status() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'astp_editor_nonce')) {
        wp_send_json_error(['message' => __('Security check failed', 'astp-versioning')]);
    }
    
    // Get post ID
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    
    if (!$post_id) {
        wp_send_json_error(['message' => __('Invalid post ID', 'astp-versioning')]);
    }
    
    // Get version info
    $current_version_id = get_post_meta($post_id, 'current_published_version', true);
    $dev_version_id = get_post_meta($post_id, 'in_development_version', true);
    
    wp_send_json_success([
        'has_published_version' => !empty($current_version_id),
        'has_dev_version' => !empty($dev_version_id),
        'current_version_id' => $current_version_id,
        'dev_version_id' => $dev_version_id,
    ]);
}
add_action('wp_ajax_astp_check_version_status', 'astp_ajax_check_version_status'); 
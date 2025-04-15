<?php
/**
 * Frontend UI Functions
 *
 * @package ASTP_Versioning
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register shortcodes for version display
 */
function astp_register_frontend_shortcodes() {
    add_shortcode('astp_version_info', 'astp_version_info_shortcode');
    add_shortcode('astp_version_history', 'astp_version_history_shortcode');
    add_shortcode('astp_version_changes', 'astp_version_changes_shortcode');
    add_shortcode('astp_document_downloads', 'astp_document_downloads_shortcode');
}
add_action('init', 'astp_register_frontend_shortcodes');

/**
 * Shortcode for displaying version info
 *
 * @param array $atts Shortcode attributes
 * @return string HTML output
 */
function astp_version_info_shortcode($atts) {
    $atts = shortcode_atts([
        'id' => null,
        'show_downloads' => true,
    ], $atts, 'astp_version_info');
    
    return astp_display_version_info($atts['id'], filter_var($atts['show_downloads'], FILTER_VALIDATE_BOOLEAN));
}

/**
 * Display version information in the frontend
 *
 * @param int  $post_id        The post ID (optional, defaults to current post)
 * @param bool $show_downloads Whether to show download links (default: true)
 * @return string HTML output
 */
function astp_display_version_info($post_id = null, $show_downloads = true) {
    if (!$post_id) {
        global $post;
        if (!isset($post->ID)) {
            return '';
        }
        $post_id = $post->ID;
    }
    
    // Get current version
    $current_version_id = get_post_meta($post_id, 'current_published_version', true);
    
    if (!$current_version_id) {
        return '';
    }
    
    // Get version details
    $version_number = get_post_meta($current_version_id, 'version_number', true);
    $release_date = get_post_meta($current_version_id, 'release_date', true);
    $formatted_date = date_i18n(get_option('date_format'), strtotime($release_date));
    
    // Get author
    $author_id = get_post_field('post_author', $current_version_id);
    $author_name = get_the_author_meta('display_name', $author_id);
    
    // Document URLs
    $pdf_url = get_post_meta($current_version_id, 'pdf_document_url', true);
    $word_url = get_post_meta($current_version_id, 'word_document_url', true);
    $github_url = get_post_meta($current_version_id, 'github_url', true);
    
    // Start output buffer
    ob_start();
    ?>
    <div class="astp-version-info">
        <div class="astp-version-info-header">
            <h3 class="astp-version-title"><?php echo esc_html(get_the_title($post_id)); ?></h3>
            <span class="astp-version-number"><?php echo esc_html($version_number); ?></span>
        </div>
        
        <div class="astp-version-details">
            <div class="astp-version-meta">
                <div class="astp-meta-item">
                    <span class="astp-meta-label"><?php _e('Release Date', 'astp-versioning'); ?></span>
                    <div class="astp-meta-value"><?php echo esc_html($formatted_date); ?></div>
                </div>
                
                <div class="astp-meta-item">
                    <span class="astp-meta-label"><?php _e('Author', 'astp-versioning'); ?></span>
                    <div class="astp-meta-value"><?php echo esc_html($author_name); ?></div>
                </div>
                
                <?php
                // Version type
                $version_type_terms = wp_get_object_terms($current_version_id, 'version_type', ['fields' => 'names']);
                if (!empty($version_type_terms)) :
                ?>
                <div class="astp-meta-item">
                    <span class="astp-meta-label"><?php _e('Version Type', 'astp-versioning'); ?></span>
                    <div class="astp-meta-value"><?php echo esc_html($version_type_terms[0]); ?></div>
                </div>
                <?php endif; ?>
            </div>
            
            <?php if ($show_downloads && ($pdf_url || $word_url || $github_url)) : ?>
            <div class="astp-document-downloads">
                <h4><?php _e('Downloads', 'astp-versioning'); ?></h4>
                <div class="astp-download-links">
                    <?php if ($pdf_url) : ?>
                    <a href="<?php echo esc_url($pdf_url); ?>" class="astp-download-button" download>
                        <i class="dashicons dashicons-pdf"></i> <?php _e('PDF Document', 'astp-versioning'); ?>
                    </a>
                    <?php endif; ?>
                    
                    <?php if ($word_url) : ?>
                    <a href="<?php echo esc_url($word_url); ?>" class="astp-download-button" download>
                        <i class="dashicons dashicons-media-document"></i> <?php _e('Word Document', 'astp-versioning'); ?>
                    </a>
                    <?php endif; ?>
                    
                    <?php if ($github_url) : ?>
                    <a href="<?php echo esc_url($github_url); ?>" class="astp-download-button" target="_blank">
                        <i class="dashicons dashicons-admin-site"></i> <?php _e('View on GitHub', 'astp-versioning'); ?>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Shortcode for displaying version history
 *
 * @param array $atts Shortcode attributes
 * @return string HTML output
 */
function astp_version_history_shortcode($atts) {
    $atts = shortcode_atts([
        'id' => null,
        'limit' => 5,
        'show_downloads' => true,
    ], $atts, 'astp_version_history');
    
    return astp_display_version_history(
        $atts['id'], 
        intval($atts['limit']), 
        filter_var($atts['show_downloads'], FILTER_VALIDATE_BOOLEAN)
    );
}

/**
 * Display version history in the frontend
 *
 * @param int  $post_id        The post ID (optional, defaults to current post)
 * @param int  $limit          Maximum number of versions to display (default: 5)
 * @param bool $show_downloads Whether to show download links (default: true)
 * @return string HTML output
 */
function astp_display_version_history($post_id = null, $limit = 5, $show_downloads = true) {
    if (!$post_id) {
        global $post;
        if (!isset($post->ID)) {
            return '';
        }
        $post_id = $post->ID;
    }
    
    // Get versions
    $versions = get_posts([
        'post_type' => 'astp_version',
        'posts_per_page' => $limit,
        'meta_query' => [
            [
                'key' => 'parent_content',
                'value' => $post_id,
                'compare' => '=',
            ],
        ],
        'tax_query' => [
            [
                'taxonomy' => 'version_status',
                'field' => 'slug',
                'terms' => 'published',
            ],
        ],
        'orderby' => 'meta_value',
        'meta_key' => 'version_number',
        'order' => 'DESC',
    ]);
    
    if (empty($versions)) {
        return '';
    }
    
    // Start output buffer
    ob_start();
    ?>
    <div class="astp-version-history">
        <div class="astp-history-header">
            <h2 class="astp-history-title"><?php _e('Version History', 'astp-versioning'); ?></h2>
        </div>
        
        <ul class="astp-version-list">
            <?php foreach ($versions as $version) : 
                // Get version details
                $version_number = get_post_meta($version->ID, 'version_number', true);
                $release_date = get_post_meta($version->ID, 'release_date', true);
                $formatted_date = date_i18n(get_option('date_format'), strtotime($release_date));
                
                // Get version type
                $version_type_terms = wp_get_object_terms($version->ID, 'version_type', ['fields' => 'names']);
                $version_type = !empty($version_type_terms) ? $version_type_terms[0] : '';
                
                // Document URLs
                $pdf_url = get_post_meta($version->ID, 'pdf_document_url', true);
                $word_url = get_post_meta($version->ID, 'word_document_url', true);
            ?>
            <li class="astp-version-list-item">
                <div class="astp-version-list-header" aria-expanded="false">
                    <h3 class="astp-version-list-title"><?php echo esc_html($version_number); ?></h3>
                    <span class="astp-version-badge"><?php echo esc_html($version_type); ?></span>
                </div>
                
                <div class="astp-version-list-meta">
                    <span><strong><?php _e('Released:', 'astp-versioning'); ?></strong> <?php echo esc_html($formatted_date); ?></span>
                    <span><strong><?php _e('Author:', 'astp-versioning'); ?></strong> <?php echo esc_html(get_the_author_meta('display_name', $version->post_author)); ?></span>
                </div>
                
                <div class="astp-version-list-content" data-version-id="<?php echo esc_attr($version->ID); ?>" data-needs-loading="true">
                    <!-- Content loaded via AJAX -->
                </div>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * AJAX handler for getting version changes for a specific version
 */
function astp_get_version_changes_callback() {
    // Check nonce
    if (!isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'astp_ajax_nonce')) {
        wp_send_json_error('Security check failed');
    }
    
    // Check if version ID is provided
    if (!isset($_POST['version_id']) || empty($_POST['version_id'])) {
        wp_send_json_error('No version ID provided');
    }
    
    $version_id = intval($_POST['version_id']);
    $version = get_post($version_id);
    
    // Check if version exists
    if (!$version || $version->post_type !== 'astp_version') {
        wp_send_json_error('Invalid version ID');
    }
    
    // Get parent post ID
    $parent_id = astp_get_version_parent_id($version_id);
    
    if (!$parent_id) {
        wp_send_json_error('Could not determine parent post');
    }
    
    // Get version info
    $version_number = get_post_meta($version_id, 'version_number', true);
    $release_date = get_post_meta($version_id, 'release_date', true);
    $formatted_date = !empty($release_date) ? date('F j, Y', strtotime($release_date)) : '';
    
    // Get changelog
    $changelog = astp_get_changelog_for_version($version_id);
    $has_changes = false;
    
    if ($changelog) {
        $changes_data = get_post_meta($changelog->ID, 'changes_data', true);
        $has_changes = !empty($changes_data) && is_array($changes_data);
    }
    
    // Start output buffering to capture HTML
    ob_start();
    
    // Output version header
    ?>
    <div class="astp-revision-header">
        <h3>Version <?php echo esc_html($version_number); ?></h3>
        <?php if (!empty($formatted_date)): ?>
        <div class="astp-revision-meta">
            Released: <?php echo esc_html($formatted_date); ?>
        </div>
        <?php endif; ?>
    </div>
    
    <div class="astp-revision-changes">
        <?php if ($has_changes): ?>
            <?php astp_display_version_changes($version_id, $parent_id); ?>
        <?php else: ?>
            <div class="astp-no-changes">No changes are available for this version.</div>
        <?php endif; ?>
    </div>
    <?php
    
    // Get the buffered content
    $content = ob_get_clean();
    
    // Send the response
    wp_send_json_success($content);
}

/**
 * Shortcode for displaying version changes
 *
 * @param array $atts Shortcode attributes
 * @return string HTML output
 */
function astp_version_changes_shortcode($atts) {
    $atts = shortcode_atts([
        'from' => null,
        'to' => null,
        'format' => 'detailed',
    ], $atts, 'astp_version_changes');
    
    if (!$atts['from'] || !$atts['to']) {
        return '<div class="notice notice-error"><p>' . __('Both from and to version IDs are required.', 'astp-versioning') . '</p></div>';
    }
    
    $changes = astp_get_version_changes($atts['from'], $atts['to']);
    return astp_render_changes_html($changes, $atts['format']);
}

/**
 * AJAX handler for comparing versions
 */
function astp_ajax_compare_versions() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'astp_frontend_nonce')) {
        wp_send_json_error(['message' => __('Security check failed', 'astp-versioning')]);
    }
    
    // Get version IDs
    $from_version_id = isset($_POST['from_version_id']) ? intval($_POST['from_version_id']) : 0;
    $to_version_id = isset($_POST['to_version_id']) ? intval($_POST['to_version_id']) : 0;
    $display_format = isset($_POST['display_format']) ? sanitize_text_field($_POST['display_format']) : 'detailed';
    
    if (!$from_version_id || !$to_version_id) {
        wp_send_json_error(['message' => __('Both from and to version IDs are required.', 'astp-versioning')]);
    }
    
    // Get changes and render
    $changes = astp_get_version_changes($from_version_id, $to_version_id);
    $html = astp_render_changes_html($changes, $display_format);
    
    wp_send_json_success(['html' => $html]);
}
add_action('wp_ajax_astp_compare_versions', 'astp_ajax_compare_versions');
add_action('wp_ajax_nopriv_astp_compare_versions', 'astp_ajax_compare_versions');

/**
 * Get changes between two versions
 * 
 * @param int $from_version_id The source version ID
 * @param int $to_version_id The target version ID
 * @return array|bool Changes data or false if error
 */
function astp_get_version_changes($from_version_id, $to_version_id) {
    // Get the changelog for the target version
    $changelog = astp_get_version_changelog($to_version_id);
    
    if ($changelog) {
        // Get changes from post meta
        $changes_data = get_post_meta($changelog->ID, 'changes_data', true);
        if (!empty($changes_data)) {
            return $changes_data;
        }
    }
    
    // Fallback: Compare blocks directly if no changelog found
    return astp_compare_block_snapshots($from_version_id, $to_version_id);
}

/**
 * Render changes HTML based on changes data and display format
 * 
 * @param array  $changes Changes data array
 * @param string $format  Display format (summary, detailed, full)
 * @return string HTML representation of changes
 */
function astp_render_changes_html($changes, $format = 'detailed') {
    if (empty($changes)) {
        return '<div class="notice notice-info"><p>' . __('No changes found between these versions.', 'astp-versioning') . '</p></div>';
    }
    
    $html = '<div class="astp-changes-display">';
    
    // Display summary counts
    $added_count = !empty($changes['added']) ? count($changes['added']) : 0;
    $removed_count = !empty($changes['removed']) ? count($changes['removed']) : 0;
    $amended_count = !empty($changes['amended']) ? count($changes['amended']) : 0;
    
    $html .= '<div class="astp-changes-summary">';
    $html .= '<h4 class="astp-changes-title">' . __('Changes Summary', 'astp-versioning') . '</h4>';
    $html .= '<div class="astp-changes-counts">';
    $html .= '<span class="astp-changes-badge astp-additions">' . $added_count . ' ' . __('Additions', 'astp-versioning') . '</span>';
    $html .= '<span class="astp-changes-badge astp-removals">' . $removed_count . ' ' . __('Removals', 'astp-versioning') . '</span>';
    $html .= '<span class="astp-changes-badge astp-amendments">' . $amended_count . ' ' . __('Amendments', 'astp-versioning') . '</span>';
    $html .= '</div>';
    $html .= '</div>';
    
    // Stop here if only summary requested
    if ($format === 'summary') {
        $html .= '</div>';
        return $html;
    }
    
    // Add detailed changes
    $html .= '<div class="astp-changes-details">';
    
    // Display additions
    if (!empty($changes['added'])) {
        $html .= '<div class="astp-changes-section">';
        $html .= '<h5 class="astp-section-name">' . __('Additions', 'astp-versioning') . '</h5>';
        $html .= '<ul class="astp-changes-list">';
        foreach ($changes['added'] as $block) {
            $block_name = isset($block['blockName']) ? $block['blockName'] : __('Unknown block', 'astp-versioning');
            $block_type = str_replace('astp/', '', $block_name);
            $block_type = ucwords(str_replace('-', ' ', $block_type));
            
            $html .= '<li class="astp-change-item">';
            $html .= '<span class="astp-change-summary">' . esc_html($block_type) . '</span>';
            
            if ($format === 'full' && isset($block['innerHTML'])) {
                $html .= '<a href="#" class="astp-toggle-details">' . __('Show details', 'astp-versioning') . '</a>';
                $html .= '<div class="astp-change-details">';
                $html .= wpautop($block['innerHTML']);
                $html .= '</div>';
            }
            
            $html .= '</li>';
        }
        $html .= '</ul>';
        $html .= '</div>';
    }
    
    // Display removals
    if (!empty($changes['removed'])) {
        $html .= '<div class="astp-changes-section">';
        $html .= '<h5 class="astp-section-name">' . __('Removals', 'astp-versioning') . '</h5>';
        $html .= '<ul class="astp-changes-list">';
        foreach ($changes['removed'] as $block) {
            $block_name = isset($block['blockName']) ? $block['blockName'] : __('Unknown block', 'astp-versioning');
            $block_type = str_replace('astp/', '', $block_name);
            $block_type = ucwords(str_replace('-', ' ', $block_type));
            
            $html .= '<li class="astp-change-item">';
            $html .= '<span class="astp-change-summary">' . esc_html($block_type) . '</span>';
            
            if ($format === 'full' && isset($block['innerHTML'])) {
                $html .= '<a href="#" class="astp-toggle-details">' . __('Show details', 'astp-versioning') . '</a>';
                $html .= '<div class="astp-change-details">';
                $html .= wpautop($block['innerHTML']);
                $html .= '</div>';
            }
            
            $html .= '</li>';
        }
        $html .= '</ul>';
        $html .= '</div>';
    }
    
    // Display amendments
    if (!empty($changes['amended'])) {
        $html .= '<div class="astp-changes-section">';
        $html .= '<h5 class="astp-section-name">' . __('Amendments', 'astp-versioning') . '</h5>';
        $html .= '<ul class="astp-changes-list">';
        foreach ($changes['amended'] as $change) {
            $block_name = isset($change['new']['blockName']) ? $change['new']['blockName'] : __('Unknown block', 'astp-versioning');
            $block_type = str_replace('astp/', '', $block_name);
            $block_type = ucwords(str_replace('-', ' ', $block_type));
            
            $html .= '<li class="astp-change-item">';
            $html .= '<span class="astp-change-summary">' . esc_html($block_type) . '</span>';
            
            if ($format === 'full' && isset($change['diff'])) {
                $html .= '<a href="#" class="astp-toggle-details">' . __('Show details', 'astp-versioning') . '</a>';
                $html .= '<div class="astp-change-details">';
                $html .= $change['diff'];
                $html .= '</div>';
            }
            
            $html .= '</li>';
        }
        $html .= '</ul>';
        $html .= '</div>';
    }
    
    $html .= '</div>'; // End astp-changes-details
    $html .= '</div>'; // End astp-changes-display
    
    return $html;
}

/**
 * Add version info to content
 *
 * @param string $content The post content
 * @return string Modified content
 */
function astp_add_version_info_to_content($content) {
    // Only apply on single test method pages
    if (!is_singular('astp_test_method')) {
        return $content;
    }
    
    // Add version info at the top
    $version_info = astp_display_version_info(null, true);
    
    return $version_info . $content;
}
add_filter('the_content', 'astp_add_version_info_to_content', 5);

/**
 * Add version history to content
 *
 * @param string $content The post content
 * @return string Modified content
 */
function astp_add_version_history_to_content($content) {
    // Only apply on single test method pages
    if (!is_singular('astp_test_method')) {
        return $content;
    }
    
    // Add version history at the bottom
    $version_history = astp_display_version_history();
    
    return $content . $version_history;
}
add_filter('the_content', 'astp_add_version_history_to_content', 15);

// Register AJAX handlers for frontend
add_action('wp_ajax_astp_get_version_changes', 'astp_get_version_changes_callback');
add_action('wp_ajax_nopriv_astp_get_version_changes', 'astp_get_version_changes_callback');

/**
 * Enqueue frontend scripts and styles
 */
function astp_enqueue_frontend_scripts() {
    wp_enqueue_style('astp-versioning-frontend', ASTP_VERSIONING_URL . 'assets/css/frontend.css', array(), ASTP_VERSIONING_VERSION);
    wp_enqueue_script('astp-versioning-frontend', ASTP_VERSIONING_URL . 'assets/js/frontend.js', array('jquery'), ASTP_VERSIONING_VERSION, true);
    
    // Localize script with AJAX URL and nonce
    wp_localize_script('astp-versioning-frontend', 'astp_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('astp_ajax_nonce')
    ));
}
add_action('wp_enqueue_scripts', 'astp_enqueue_frontend_scripts'); 
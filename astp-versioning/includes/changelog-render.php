<?php
/**
 * Server-side rendering for the changelog block
 *
 * @package ASTP_Versioning
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Include versioning functions if not already included
if (!function_exists('astp_get_version_history')) {
    require_once dirname(__FILE__) . '/versioning.php';
}

/**
 * Configure the changelog block for server-side rendering
 */
function astp_configure_changelog_block() {
    // Check if the block is already registered to prevent duplicate registrations
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('Checking if changelog block is already registered');
    }
    
    if (WP_Block_Type_Registry::get_instance()->is_registered('uswds-gutenberg/changelog-block')) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Block uswds-gutenberg/changelog-block already registered, skipping registration');
        }
        return;
    }
    
    // Skip if block editor is not available
    if (!function_exists('register_block_type')) {
        return;
    }
    
    // Register the full-featured version (but don't set it as the editor_script)
    wp_register_script(
        'astp-changelog-block',
        ASTP_VERSIONING_URL . 'custom-blocks/uswds-changelog-block.js',
        array('wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-data', 'wp-block-editor', 'wp-i18n'),
        filemtime(ASTP_VERSIONING_DIR . 'custom-blocks/uswds-changelog-block.js'),
        true
    );
    
    // Register the simple version (but don't set it as the editor_script)
    wp_register_script(
        'astp-changelog-block-fixed',
        ASTP_VERSIONING_URL . 'custom-blocks/changelog-block-fixed.js',
        array('wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-data', 'wp-block-editor', 'wp-i18n'),
        filemtime(ASTP_VERSIONING_DIR . 'custom-blocks/changelog-block-fixed.js'),
        true
    );
    
    // Register the wrapper that will handle loading either script
    wp_register_script(
        'astp-changelog-block-wrapper',
        ASTP_VERSIONING_URL . 'custom-blocks/changelog-block-wrapper.js',
        array(
            'wp-blocks', 
            'wp-element', 
            'wp-editor', 
            'wp-components', 
            'wp-data',
            'wp-block-editor',
            'wp-i18n',
            'astp-changelog-block',
            'astp-changelog-block-fixed'
        ),
        filemtime(ASTP_VERSIONING_DIR . 'custom-blocks/changelog-block-wrapper.js'),
        true
    );
    
    // Register the full-featured version (but don't set it as the editor_script)
    wp_register_script(
        'astp-changelog-block-fixed2',
        ASTP_VERSIONING_URL . 'custom-blocks/uswds-changelog-block-fixed2.js',
        array('wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-data', 'wp-block-editor', 'wp-i18n'),
        filemtime(ASTP_VERSIONING_DIR . 'custom-blocks/uswds-changelog-block-fixed2.js'),
        true
    );
    
    // Register the initializer script
    wp_register_script(
        'astp-changelog-block-initializer',
        ASTP_VERSIONING_URL . 'custom-blocks/changelog-block-initializer.js',
        array('wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-data', 'wp-block-editor', 'wp-i18n'),
        filemtime(ASTP_VERSIONING_DIR . 'custom-blocks/changelog-block-initializer.js'),
        true
    );
    
    // Define block attributes
    $attributes = array(
        'postId' => array(
            'type' => 'number',
            'default' => 0
        ),
        'title' => array(
            'type' => 'string',
            'default' => 'Certification Companion Guide Changelog'
        ),
        'subtitle' => array(
            'type' => 'string',
            'default' => 'The following changelog applies to:'
        ),
        'subtitleEmphasis' => array(
            'type' => 'string',
            'default' => '§ 170.315(g)(10) Standardized API for patient and population services'
        ),
        'enableFilters' => array(
            'type' => 'boolean',
            'default' => true
        ),
        'enableTabSwitching' => array(
            'type' => 'boolean',
            'default' => true
        )
    );
    
    // Register the block type using our initializer script
    register_block_type('uswds-gutenberg/changelog-block', array(
        'api_version' => 2,
        'attributes' => $attributes,
        'render_callback' => 'astp_render_changelog_block',
        'editor_script' => 'astp-changelog-block-initializer',
        'editor_style' => 'astp-changelog-styles',
        'category' => 'common',
    ));
    
    if (WP_DEBUG) {
        error_log('Configured USWDS changelog block with smart wrapper'); 
    }
}
add_action('init', 'astp_configure_changelog_block', 20);

/**
 * Render a changelog block from snapshots
 * 
 * @param array $attributes Block attributes from Gutenberg
 * @return string HTML for the changelog
 */
function astp_render_changelog_block($attributes) {
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('CHANGELOG RENDER - Function called with attributes: ' . print_r($attributes, true));
    }
    
    // Get post ID from attributes or current post
    $post_id = isset($attributes['postId']) && intval($attributes['postId']) > 0 ? 
               intval($attributes['postId']) : get_the_ID();
    
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('CHANGELOG RENDER - Using post ID: ' . $post_id);
    }
    
    // Get versions history
    $versions = astp_get_version_history('ccg', $post_id);
    
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('CHANGELOG RENDER - Found ' . count($versions) . ' versions');
    }
    
    if (empty($versions)) {
        return '<div class="astp-changelog-empty">No version history available for this document.</div>';
    }
    
    // Convert version history to snapshots format expected by the rendering logic
    $snapshots = array();
    foreach ($versions as $version) {
        $version_id = isset($version['version_id']) ? $version['version_id'] : 0;
        if (!$version_id) continue;
        
        // Get block snapshots for this version
        $block_snapshots = get_post_meta($version_id, 'block_snapshots', true);
        if (empty($block_snapshots) || !is_array($block_snapshots)) {
    if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('CHANGELOG RENDER - No block snapshots found for version ID: ' . $version_id);
            }
            continue;
        }
        
        $snapshots[] = array(
            'version' => isset($version['version']) ? $version['version'] : '',
            'timestamp' => strtotime(isset($version['date']) ? $version['date'] : ''),
            'blocks' => $block_snapshots
        );
    }
    
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('CHANGELOG RENDER - Prepared ' . count($snapshots) . ' snapshots for rendering');
    }
    
    if (empty($snapshots)) {
        return '<div class="astp-changelog-empty">Unable to load changelog data.</div>';
    }
    
    // Get block attributes with defaults
    $title = isset($attributes['title']) ? $attributes['title'] : 'Certification Companion Guide Changelog';
    $subtitle = isset($attributes['subtitle']) ? $attributes['subtitle'] : 'The following changelog applies to:';
    $subtitle_emphasis = isset($attributes['subtitleEmphasis']) ? $attributes['subtitleEmphasis'] : '§ 170.315(g)(10) Standardized API for patient and population services';
    $enable_filters = isset($attributes['enableFilters']) ? $attributes['enableFilters'] : true;
    $enable_tab_switching = isset($attributes['enableTabSwitching']) ? $attributes['enableTabSwitching'] : true;
    
    // Start output buffer
    ob_start();
    
    // Main container
    ?>
    <div class="tabs-panel__changelog">
        <div class="mobile-its-select">
            <label for="section-select">IN THIS SECTION</label>
            <select id="section-select"></select>
            <a class="usa-button btn btn--icon-right view-all mobile-only" href="#" aria-label="View all archives">
                View Archives
            </a>
        </div>
        <div class="changelog-tab">
            <div class="top">
                <h2><?php echo esc_html($title); ?></h2>
                <p>
                    <?php echo esc_html($subtitle); ?>
                    <br>
                    <strong><?php echo esc_html($subtitle_emphasis); ?></strong>
                </p>
            </div>
            
            <?php if ($enable_tab_switching): ?>
            <div class="tabs hit-ccg-single__pills">
                <ul class="tabs-primary">
                    <li>
                        <a href="#tab-cert-changelog">Certification Companion Guide</a>
                    </li>
                    <li>
                        <a href="#testing-changelog">Test Procedures</a>
                    </li>
                </ul>
            </div>
            <?php endif; ?>
            
            <div class="tabs-panels">
                <div class="tabs-panel" id="tab-cert-changelog">
                    <?php echo render_changelog_snapshots($snapshots); ?>
                </div>
                
                <?php if ($enable_tab_switching): ?>
                <div class="tabs-panel" id="testing-changelog">
                    <div class="changelog-container">
                        <div class="timeline">
                            <ul>
                                <li>
                                                <div class="version">
                                                    <div class="inner">
                                            <p>No Test Procedure versions found.</p>
                                                    </div>
                                                </div>
                                            </li>
                            </ul>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
                                            <?php
    
    // Get output buffer contents and return
    $output = ob_get_clean();
    return $output;
}

/**
 * Render snapshots using the improved text diffing while matching the original UI structure
 * 
 * @param array $snapshots Array of snapshot data with metadata
 * @return string HTML for the changelog
 */
function render_changelog_snapshots($snapshots) {
    if (empty($snapshots) || !is_array($snapshots)) {
        return '<div class="astp-changelog-empty">No version history available for this document.</div>';
    }

    // Convert versions to numeric values for comparison
    foreach ($snapshots as &$snapshot) {
        if (isset($snapshot['version'])) {
            $snapshot['version_numeric'] = floatval(str_replace('v', '', $snapshot['version']));
        } else {
            $snapshot['version_numeric'] = 0;
        }
    }
    unset($snapshot); // Break the reference
    
    // Create an associative array to easily find snapshots by version number
    $snapshots_by_version = array();
    foreach ($snapshots as $snapshot) {
        if (isset($snapshot['version_numeric'])) {
            $snapshots_by_version[$snapshot['version_numeric']] = $snapshot;
        }
    }
    
    // Sort version numbers for comparison
    $version_numbers = array_keys($snapshots_by_version);
    sort($version_numbers); // Ascending order for comparison purposes

    // Sort snapshots by version number descending (primary) and timestamp descending (secondary)
    usort($snapshots, function($a, $b) {
        // First sort by version number if available
        if (isset($a['version_numeric']) && isset($b['version_numeric'])) {
            if ($a['version_numeric'] != $b['version_numeric']) {
                return $b['version_numeric'] - $a['version_numeric']; // Descending order
            }
        }
        
        // Fallback to timestamp if versions are equal or not available
        if (isset($a['timestamp']) && isset($b['timestamp'])) {
            return $b['timestamp'] - $a['timestamp']; // Descending order
        }
        
        return 0;
    });

    // Start output with container
    $output = '<div class="changelog-container" tabindex="0"><div class="timeline"><ul>';
    
    // Process each version
    foreach ($snapshots as $snapshot) {
        if (!isset($snapshot['blocks']) || !is_array($snapshot['blocks'])) {
            continue;
        }
        
        $version = isset($snapshot['version']) ? $snapshot['version'] : '';
        $version_numeric = isset($snapshot['version_numeric']) ? $snapshot['version_numeric'] : 0;
        $date = isset($snapshot['timestamp']) ? date('F j, Y', $snapshot['timestamp']) : '';
        $version_id = 'version-' . str_replace('.', '-', $version);
        
        $output .= '<li class="change" id="' . esc_attr($version_id) . '">';
        $output .= '<div class="date">' . esc_html($date) . '</div>';
        $output .= '<div class="version"><div class="inner">';
        $output .= '<h3 class="version-number">Version ' . esc_html($version) . '</h3>';
        
        // Find previous version for comparison
        $previous_versions = array();
        foreach ($version_numbers as $v) {
            if ($v < $version_numeric) {
                $previous_versions[] = $v;
            }
        }
        rsort($previous_versions); // Get them in descending order
        
        // Process blocks to determine changes
        $changes_by_section = array();
        
        foreach ($snapshot['blocks'] as $block_id => $block_data) {
            if (!isset($block_data['blockName']) || $block_data['blockName'] !== 'uswds-gutenberg/clarification-block') {
                continue;
            }
            
            $change_type = 'added'; // Default change type
            $previous_content = null;
            $block_existed_before = false;
            
            // Check if this block existed in a previous version
            foreach ($previous_versions as $prev_version) {
                if (isset($snapshots_by_version[$prev_version]['blocks'][$block_id])) {
                    $block_existed_before = true;
                    // Block existed before - determine if it changed
                    $prev_block = $snapshots_by_version[$prev_version]['blocks'][$block_id];
                    
                    // Extract content from previous version for comparison
                    $prev_content = astp_extract_clarification_content($prev_block, '', null);
                    $curr_content = astp_extract_clarification_content($block_data, '', null);
                    
                    if ($prev_content === $curr_content) {
                        // Block is unchanged - no need to show in changelog
                        $change_type = 'unchanged';
                                                                                        } else {
                        // Block was amended - we'll determine the exact change type during diff
                        $change_type = 'amended';
                        $previous_content = $prev_content;
                    }
                    
                                                                                                            break;
                                                                                                        }
                                                                                                    }
            
            // If not unchanged, show in changelog
            if ($change_type !== 'unchanged') {
                // Get section info from block data
                $section_name = '';
                $section_title = '';
                
                if (isset($block_data['attrs']['sections']) && is_array($block_data['attrs']['sections'])) {
                    foreach ($block_data['attrs']['sections'] as $section) {
                        if (isset($section['version'])) {
                            $section_name = $section['version'];
                        }
                        if (isset($section['heading'])) {
                            $section_title = $section['heading'];
                        }
                    }
                }
                
                // Extract paragraph info from section name
                $paragraph_ref = '';
                if (preg_match('/Paragraph\s*\((.*?)\)/', $section_name, $matches)) {
                    $paragraph_ref = $matches[1];
                }
                
                if (empty($paragraph_ref)) {
                    $paragraph_ref = preg_replace('/[^(]*\(([^)]+)\).*/', '$1', $section_name);
                }
                
                // Create a section key
                $section_key = $paragraph_ref . ': ' . $section_title;
                
                if (!isset($changes_by_section[$section_key])) {
                    $changes_by_section[$section_key] = array(
                        'paragraph' => $paragraph_ref,
                        'title' => $section_title,
                        'changes' => array(
                            'added' => array(),
                            'removed' => array(),
                            'amended' => array()
                        )
                    );
                }
                
                // Extract and format content with highlighting based on change type
                if ($change_type === 'amended' && !empty($previous_content)) {
                    // For amendments, we'll use the diffing function which will auto-categorize
                    $curr_content = astp_extract_clarification_content($block_data, '', null);
                    
                    // Use text diffing to determine change type
                    if (class_exists('\Text_Diff')) {
                        $extracted_content = astp_format_text_diff($previous_content, $curr_content);
                                                                                } else {
                        $extracted_content = astp_simple_text_diff($previous_content, $curr_content);
                    }
                    
                    // Add the content to the appropriate change type category
                    if (strpos($extracted_content, 'class="added"') !== false) {
                        $changes_by_section[$section_key]['changes']['added'][] = array(
                            'type' => 'added',
                            'content' => $extracted_content
                        );
                    } else if (strpos($extracted_content, 'class="removed"') !== false) {
                        $changes_by_section[$section_key]['changes']['removed'][] = array(
                            'type' => 'removed',
                            'content' => $extracted_content
                        );
                                                                                        } else {
                        $changes_by_section[$section_key]['changes']['amended'][] = array(
                            'type' => 'amended',
                            'content' => $extracted_content
                        );
                    }
                                                                                } else {
                    // For added content (new blocks)
                    $extracted_content = astp_extract_clarification_content($block_data, $change_type, null);
                    $changes_by_section[$section_key]['changes'][$change_type][] = array(
                        'type' => $change_type,
                        'content' => $extracted_content
                    );
                }
            }
        }
        
        // Check for removed blocks
        if (!empty($previous_versions)) {
            $prev_version = $previous_versions[0]; // Most recent previous version
            $prev_snapshot = isset($snapshots_by_version[$prev_version]) ? $snapshots_by_version[$prev_version] : null;
            
            if ($prev_snapshot && isset($prev_snapshot['blocks']) && is_array($prev_snapshot['blocks'])) {
                foreach ($prev_snapshot['blocks'] as $block_id => $block_data) {
                    if (!isset($snapshot['blocks'][$block_id]) && 
                        isset($block_data['blockName']) && 
                        $block_data['blockName'] === 'uswds-gutenberg/clarification-block') {
                        
                        // Get section info from block data
                        $section_name = '';
                        $section_title = '';
                        
                        if (isset($block_data['attrs']['sections']) && is_array($block_data['attrs']['sections'])) {
                            foreach ($block_data['attrs']['sections'] as $section) {
                                if (isset($section['version'])) {
                                    $section_name = $section['version'];
                                }
                                if (isset($section['heading'])) {
                                    $section_title = $section['heading'];
                                }
                            }
                        }
                        
                                                            // Extract paragraph info from section name
                                                            $paragraph_ref = '';
                        if (preg_match('/Paragraph\s*\((.*?)\)/', $section_name, $matches)) {
                            $paragraph_ref = $matches[1];
                        }
                        
                        if (empty($paragraph_ref)) {
                            $paragraph_ref = preg_replace('/[^(]*\(([^)]+)\).*/', '$1', $section_name);
                        }
                        
                        // Create a section key
                        $section_key = $paragraph_ref . ': ' . $section_title;
                        
                        if (!isset($changes_by_section[$section_key])) {
                            $changes_by_section[$section_key] = array(
                                'paragraph' => $paragraph_ref,
                                'title' => $section_title,
                                'changes' => array(
                                    'added' => array(),
                                    'removed' => array(),
                                    'amended' => array()
                                )
                            );
                        }
                        
                        // Extract content with removed highlighting
                        $extracted_content = astp_extract_clarification_content($block_data, 'removed', null);
                        
                        // Add to the section changes
                        $changes_by_section[$section_key]['changes']['removed'][] = array(
                            'type' => 'removed',
                            'content' => $extracted_content
                        );
                    }
                }
            }
        }
        
        // Format changes by section
        if (!empty($changes_by_section)) {
            $output .= '<ul class="changes">';
            
            foreach ($changes_by_section as $section_key => $section_data) {
                $output .= '<li>';
                $output .= '<div class="changelog-info">';
                $output .= '<div class="paragraph">Paragraph (' . esc_html($section_data['paragraph']) . ')</div>';
                $output .= '<h4 class="title">' . esc_html($section_data['title']) . '</h4>';
                
                // Output added changes
                if (!empty($section_data['changes']['added'])) {
                    $output .= '<h5 class="action">Added:</h5>';
                    foreach ($section_data['changes']['added'] as $change) {
                        $output .= '<p class="content">' . astp_prepare_html_content($change['content']) . '</p>';
                    }
                }
                
                // Output removed changes
                if (!empty($section_data['changes']['removed'])) {
                    $output .= '<h5 class="action">Removed:</h5>';
                    foreach ($section_data['changes']['removed'] as $change) {
                        $output .= '<p class="content">' . astp_prepare_html_content($change['content']) . '</p>';
                    }
                }
                
                // Output amended changes
                if (!empty($section_data['changes']['amended'])) {
                    $output .= '<h5 class="action">Amended:</h5>';
                    foreach ($section_data['changes']['amended'] as $change) {
                        $output .= '<p class="content">' . astp_prepare_html_content($change['content']) . '</p>';
                    }
                }
                
                $output .= '</div>'; // .changelog-info
                $output .= '</li>';
            }
            
            $output .= '</ul>'; // .changes
                                                                                } else {
            $output .= '<p>No changes recorded for this version.</p>';
        }
        
        $output .= '</div></div>'; // .inner, .version
        $output .= '</li>'; // .change
    }
    
    $output .= '</ul></div>'; // .timeline, ul
    
    // Add sidebar with filters
    $output .= '<div class="sidebar">';
    $output .= '<h4>FILTER BY TYPE</h4>';
    $output .= '<ul class="filter-type">';
    $output .= '<li><button class="selected filter-btn" type="button">All</button></li>';
    $output .= '<li><button class="filter-btn" type="button">Added</button></li>';
    $output .= '<li><button class="filter-btn" type="button">Amended</button></li>';
    $output .= '<li><button class="filter-btn" type="button">Removed</button></li>';
    $output .= '</ul>';
    
    // Recent releases
    $output .= '<h4 class="recent-releases-title">RECENT RELEASES</h4>';
    $output .= '<ul class="recent-releases">';
    $output .= '<div class="indicator" style="transform: translateY(0px);"></div>';
    
    // Add links to each version
    $first = true;
    foreach ($snapshots as $snapshot) {
        $version = isset($snapshot['version']) ? $snapshot['version'] : '';
        $date = isset($snapshot['timestamp']) ? date('F d, Y', $snapshot['timestamp']) : '';
        $version_id = 'version-' . str_replace('.', '-', $version);
        
        $output .= '<li class="' . ($first ? 'current' : '') . '">';
        $output .= '<a href="#' . $version_id . '" class="' . ($first ? 'current' : '') . '">';
        $output .= 'Version ' . esc_html($version) . ' <span class="date">' . esc_html($date) . '</span>';
        $output .= '</a></li>';
        
        $first = false;
    }
    
    $output .= '</ul>';
    $output .= '<a class="usa-button btn btn--icon-right" href="#" aria-label="View all archives">View Archives</a>';
    $output .= '</div>'; // .sidebar
    
    $output .= '</div>'; // .changelog-container
    
    return $output;
}

/**
 * Prepare HTML content by decoding entities before passing to wp_kses_post
 * 
 * @param string $content The content to prepare
 * @return string The sanitized content
 */
function astp_prepare_html_content($content) {
    $decoded = html_entity_decode($content, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    return wp_kses_post($decoded);
}

/**
 * Format change content with markup for added/removed/amended text
 *
 * @param string $content The change content
 * @param string $type The change type ('added', 'removed', or 'amended')
 * @return string The formatted content
 */
function astp_format_change_content($content, $type) {
    // Process special markers in the content
    $formatted_content = preg_replace_callback(
        '/\[\[(.*?)\]\]/',
        function($matches) use ($type) {
            return '<mark class="' . $type . '">' . $matches[1] . '</mark>';
        },
        $content
    );
    
    return $formatted_content;
}

/**
 * Register and enqueue the changelog block styles and scripts
 */
function astp_enqueue_changelog_assets() {
    // Handle CSS file
    $css_file = ASTP_VERSIONING_DIR . 'assets/css/changelog-block.css';
    $css_url = ASTP_VERSIONING_URL . 'assets/css/changelog-block.css';
    
    // Create the CSS directory if it doesn't exist
    if (!file_exists(dirname($css_file))) {
        wp_mkdir_p(dirname($css_file));
    }
    
    // Create an empty CSS file if it doesn't exist
    if (!file_exists($css_file)) {
        file_put_contents($css_file, '/* Changelog Block Styles */');
    }
    
    // Enqueue the stylesheet
    wp_register_style(
        'astp-changelog-styles',
        $css_url,
        array(),
        file_exists($css_file) ? filemtime($css_file) : ASTP_VERSIONING_VERSION
    );
    
    // Handle JS file
    $js_file = ASTP_VERSIONING_DIR . 'assets/js/changelog-block.js';
    $js_url = ASTP_VERSIONING_URL . 'assets/js/changelog-block.js';
    
    // Create the JS directory if it doesn't exist
    if (!file_exists(dirname($js_file))) {
        wp_mkdir_p(dirname($js_file));
    }
    
    // Register the script (will be enqueued in render function when block is used)
    wp_register_script(
        'astp-changelog-frontend',
        $js_url,
        array('jquery'),
        file_exists($js_file) ? filemtime($js_file) : ASTP_VERSIONING_VERSION,
        true
    );
}
add_action('init', 'astp_enqueue_changelog_assets');

/**
 * Debug shortcode to display version data for a specific post
 */
function astp_version_debug_shortcode($atts) {
    $atts = shortcode_atts(array(
        'post_id' => get_the_ID(),
        'doc_type' => 'both', // can be 'ccg', 'tp', or 'both'
    ), $atts, 'astp_version_debug');
    
    $post_id = intval($atts['post_id']);
    $doc_type = $atts['doc_type'];
    
    ob_start();
    
    echo '<div class="astp-debug-container" style="background: #f5f5f5; padding: 20px; margin: 20px 0; border: 1px solid #ddd; font-family: monospace;">';
    echo '<h2>Version Debug for Post ID: ' . esc_html($post_id) . '</h2>';
    
    if ($doc_type === 'both' || $doc_type === 'ccg') {
        echo '<h3>CCG Versions:</h3>';
        $ccg_versions = astp_get_version_history('ccg', $post_id);
        if (empty($ccg_versions)) {
            echo '<p>No CCG versions found.</p>';
            
            // Check for any versions that might exist without being properly categorized
            $basic_args = array(
                'post_type' => 'astp_version',
                'posts_per_page' => -1,
                'post_parent' => $post_id,
            );
            $basic_versions = get_posts($basic_args);
            
            if (!empty($basic_versions)) {
                echo '<p>Found ' . count($basic_versions) . ' raw version posts. Details:</p>';
                echo '<ul>';
                foreach ($basic_versions as $bv) {
                    $dt = get_post_meta($bv->ID, 'document_type', true);
                    $vn = get_post_meta($bv->ID, 'version_number', true);
                    $cl = get_post_meta($bv->ID, 'changelog_id', true);
                    $status_terms = wp_get_object_terms($bv->ID, 'version_status', array('fields' => 'slugs'));
                    
                    echo '<li>';
                    echo 'ID: ' . esc_html($bv->ID) . ', ';
                    echo 'Title: ' . esc_html($bv->post_title) . ', ';
                    echo 'Document Type: ' . esc_html($dt) . ', ';
                    echo 'Version: ' . esc_html($vn) . ', ';
                    echo 'Status: ' . esc_html(implode(',', $status_terms)) . ', ';
                    echo 'Changelog ID: ' . esc_html($cl);
                    
                    if ($cl) {
                        // Check different data storage formats
                        $changes_data = get_post_meta($cl, 'changes_data', true);
                        $changelog_block_data = get_post_meta($cl, 'changelog_block_data', true);
                        $raw_changes = get_post_meta($cl, 'raw_changes_data', true);
                        
                        echo '<br>Changelog Data Found: ';
                        echo !empty($changes_data) ? 'changes_data, ' : '';
                        echo !empty($changelog_block_data) ? 'changelog_block_data, ' : '';
                        echo !empty($raw_changes) ? 'raw_changes_data, ' : '';
                        
                        if (empty($changes_data) && empty($changelog_block_data) && empty($raw_changes)) {
                            echo 'None';
                        }
                    }
                    
                    echo '</li>';
                }
                echo '</ul>';
            }
        } else {
            echo '<p>Found ' . count($ccg_versions) . ' CCG versions.</p>';
            
            foreach ($ccg_versions as $index => $version) {
                echo '<details open>';
                echo '<summary>Version ' . esc_html($version['version']) . ' (' . esc_html($version['date']) . ') - ID: ' . 
                     esc_html(isset($version['version_id']) ? $version['version_id'] : 'N/A') . '</summary>';
                
                echo '<div style="margin-left: 20px;">';
                
                // Get raw changes data directly from changelog meta
                $cl_id = isset($version['changelog_id']) ? $version['changelog_id'] : 0;
                $changes_found = false;
                
                if ($cl_id) {
                    $raw_changes_data = get_post_meta($cl_id, 'changes_data', true);
                    
                    if (!empty($raw_changes_data)) {
                        $changes_found = true;
                        echo '<h4>Raw Changes Data:</h4>';
                        echo '<div style="background: #fff; padding: 10px; margin-bottom: 15px; border: 1px solid #ddd; max-height: 300px; overflow: auto;">';
                        
                        // Display the added changes
                        if (!empty($raw_changes_data['added'])) {
                            echo '<h5 style="color: green;">Added (' . count($raw_changes_data['added']) . '):</h5>';
                            echo '<ul>';
                            foreach ($raw_changes_data['added'] as $change) {
                                echo '<li>';
                                if (isset($change['section']) && isset($change['section']['full_text'])) {
                                    echo '<strong>Section:</strong> ' . esc_html($change['section']['full_text']) . '<br>';
                                }
                                if (isset($change['block']) && isset($change['block']['blockName'])) {
                                    echo '<strong>Block Type:</strong> ' . esc_html($change['block']['blockName']) . '<br>';
                                }
                                
                                // Extract paragraph content from regulatory block if available
                                if (isset($change['block']['attrs']['paragraphs'])) {
                                    foreach ($change['block']['attrs']['paragraphs'] as $paragraph) {
                                        echo '<div style="margin: 5px 0 5px 15px; padding: 5px; border-left: 3px solid #4CAF50;">';
                                        echo '<strong>Paragraph ' . esc_html($paragraph['number']) . ':</strong> ';
                                        echo wp_kses_post($paragraph['text']);
                                        echo '</div>';
                                    }
                                } else if (isset($change['block']['innerHTML'])) {
                                    echo '<div style="margin: 5px 0; padding: 5px; border-left: 3px solid #4CAF50; max-height: 100px; overflow: auto;">';
                                    echo '<strong>Content:</strong> ';
                                    echo esc_html(substr($change['block']['innerHTML'], 0, 150)) . (strlen($change['block']['innerHTML']) > 150 ? '...' : '');
                                    echo '</div>';
                                }
                                
                                echo '</li>';
                            }
                            echo '</ul>';
                        }
                        
                        // Display the removed changes
                        if (!empty($raw_changes_data['removed'])) {
                            echo '<h5 style="color: red;">Removed (' . count($raw_changes_data['removed']) . '):</h5>';
                            echo '<ul>';
                            foreach ($raw_changes_data['removed'] as $change) {
                                echo '<li>';
                                if (isset($change['section']) && isset($change['section']['full_text'])) {
                                    echo '<strong>Section:</strong> ' . esc_html($change['section']['full_text']) . '<br>';
                                }
                                if (isset($change['block']) && isset($change['block']['blockName'])) {
                                    echo '<strong>Block Type:</strong> ' . esc_html($change['block']['blockName']) . '<br>';
                                }
                                
                                // Extract paragraph content from regulatory block if available
                                if (isset($change['block']['attrs']['paragraphs'])) {
                                    foreach ($change['block']['attrs']['paragraphs'] as $paragraph) {
                                        echo '<div style="margin: 5px 0 5px 15px; padding: 5px; border-left: 3px solid #F44336;">';
                                        echo '<strong>Paragraph ' . esc_html($paragraph['number']) . ':</strong> ';
                                        echo wp_kses_post($paragraph['text']);
                                        echo '</div>';
                                    }
                                } else if (isset($change['block']['innerHTML'])) {
                                    echo '<div style="margin: 5px 0; padding: 5px; border-left: 3px solid #F44336; max-height: 100px; overflow: auto;">';
                                    echo '<strong>Content:</strong> ';
                                    echo esc_html(substr($change['block']['innerHTML'], 0, 150)) . (strlen($change['block']['innerHTML']) > 150 ? '...' : '');
                                    echo '</div>';
                                }
                                
                                echo '</li>';
                            }
                            echo '</ul>';
                        }
                        
                        // Display the amended changes
                        if (!empty($raw_changes_data['amended'])) {
                            echo '<h5 style="color: orange;">Amended (' . count($raw_changes_data['amended']) . '):</h5>';
                            echo '<ul>';
                            foreach ($raw_changes_data['amended'] as $change) {
                                echo '<li>';
                                if (isset($change['section']) && isset($change['section']['full_text'])) {
                                    echo '<strong>Section:</strong> ' . esc_html($change['section']['full_text']) . '<br>';
                                }
                                
                                // Display old and new block details
                                if (isset($change['old_block']) && isset($change['old_block']['blockName'])) {
                                    echo '<strong>Block Type:</strong> ' . esc_html($change['old_block']['blockName']) . '<br>';
                                    
                                    // Extract old content
                                    echo '<div style="margin: 5px 0; padding: 5px; border-left: 3px solid #F44336; max-height: 100px; overflow: auto;">';
                                    echo '<strong>Old Content:</strong> ';
                                    
                                    if (isset($change['old_block']['attrs']['paragraphs'])) {
                                        foreach ($change['old_block']['attrs']['paragraphs'] as $paragraph) {
                                            echo '<div style="margin: 5px 0 0 10px;">';
                                            echo '<strong>Paragraph ' . esc_html($paragraph['number']) . ':</strong> ';
                                            echo wp_kses_post($paragraph['text']);
                                            echo '</div>';
                                        }
                                    } else if (isset($change['old_block']['innerHTML'])) {
                                        echo esc_html(substr($change['old_block']['innerHTML'], 0, 150)) . (strlen($change['old_block']['innerHTML']) > 150 ? '...' : '');
                                    }
                                    
                                    echo '</div>';
                                }
                                
                                if (isset($change['new_block'])) {
                                    // Extract new content
                                    echo '<div style="margin: 5px 0; padding: 5px; border-left: 3px solid #4CAF50; max-height: 100px; overflow: auto;">';
                                    echo '<strong>New Content:</strong> ';
                                    
                                    if (isset($change['new_block']['attrs']['paragraphs'])) {
                                        foreach ($change['new_block']['attrs']['paragraphs'] as $paragraph) {
                                            echo '<div style="margin: 5px 0 0 10px;">';
                                            echo '<strong>Paragraph ' . esc_html($paragraph['number']) . ':</strong> ';
                                            echo wp_kses_post($paragraph['text']);
                                            echo '</div>';
                                        }
                                    } else if (isset($change['new_block']['innerHTML'])) {
                                        echo esc_html(substr($change['new_block']['innerHTML'], 0, 150)) . (strlen($change['new_block']['innerHTML']) > 150 ? '...' : '');
                                    }
                                    
                                    echo '</div>';
                                }
                                
                                echo '</li>';
                            }
                            echo '</ul>';
                        }
                        
                        echo '</div>';
                    }
                }
                
                if (empty($version['changes']) && !$changes_found) {
                    echo '<p>No changes found for this version.</p>';
                    
                    // Show metadata about the changelog
                    if (!empty($version['changelog_id'])) {
                        echo '<p>Changelog ID: ' . esc_html($version['changelog_id']) . '</p>';
                        
                        // Check for data in different formats
                        $cl_id = $version['changelog_id'];
                        $changes_data = get_post_meta($cl_id, 'changes_data', true);
                        $changelog_block_data = get_post_meta($cl_id, 'changelog_block_data', true);
                        $raw_changes = get_post_meta($cl_id, 'raw_changes_data', true);
                        
                        echo '<p>Raw data check:</p>';
                        echo '<ul>';
                        echo '<li>changes_data: ' . (!empty($changes_data) ? 'Found' : 'Not found') . '</li>';
                        echo '<li>changelog_block_data: ' . (!empty($changelog_block_data) ? 'Found' : 'Not found') . '</li>';
                        echo '<li>raw_changes_data: ' . (!empty($raw_changes) ? 'Found' : 'Not found') . '</li>';
                        echo '</ul>';
                    }
                } else if (!empty($version['changes'])) {
                    echo '<p>Changes found: ' . count($version['changes']) . '</p>';
                    
                    // Group changes by section
                    $changes_by_section = array();
                    foreach ($version['changes'] as $change) {
                        $section = isset($change['section']) ? $change['section'] : 'General';
                        if (!isset($changes_by_section[$section])) {
                            $changes_by_section[$section] = array();
                        }
                        $changes_by_section[$section][] = $change;
                    }
                    
                    // Display changes grouped by section
                    if (!empty($changes_by_section)) {
                        echo '<ul class="changes">';
                        foreach ($changes_by_section as $section => $section_changes) {
                            echo '<li class="change-section">';
                            echo '<div class="changelog-info">';
                            echo '<div class="paragraph">' . esc_html($section) . '</div>';
                            
                            foreach ($section_changes as $change) {
                                $change_type = isset($change['type']) ? $change['type'] : 'general';
                                $change_title = isset($change['title']) ? $change['title'] : '';
                                $change_content = isset($change['content']) ? $change['content'] : '';
                                
                                echo '<div class="change-item change-' . esc_attr($change_type) . '">';
                                if (!empty($change_title)) {
                                    echo '<h4 class="title">' . esc_html($change_title) . '</h4>';
                                }
                                
                                echo '<h5 class="action">' . ucfirst(esc_html($change_type)) . ':</h5>';
                                echo '<div class="content">' . wp_kses_post($change_content) . '</div>';
                                echo '</div>';
                            }
                            
                            echo '</div>';
                            echo '</li>';
                        }
                        echo '</ul>';
                    } else {
                        echo '<p class="no-changes-message">No changes could be processed for this version.</p>';
                    }
                }
                
                echo '</div>';
                echo '</details>';
            }
        }
    }
    
    echo '</div>';
    
    return ob_get_clean();
}
add_shortcode('astp_version_debug', 'astp_version_debug_shortcode');

/**
 * Debug shortcode to display raw changelog data
 */
function astp_raw_changelog_debug_shortcode($atts) {
    $atts = shortcode_atts(array(
        'post_id' => get_the_ID(),
        'changelog_id' => 0,
        'version_id' => 0,
    ), $atts, 'astp_raw_changelog_debug');
    
    $post_id = intval($atts['post_id']);
    $changelog_id = intval($atts['changelog_id']);
    $version_id = intval($atts['version_id']);
    
    if (!$changelog_id && $version_id) {
        $changelog_id = get_post_meta($version_id, 'changelog_id', true);
    }
    
    if (!$changelog_id) {
        return '<p>No changelog ID provided or found.</p>';
    }
    
    ob_start();
    
    echo '<div class="astp-debug-container" style="background: #f5f5f5; padding: 20px; margin: 20px 0; border: 1px solid #ddd; font-family: monospace;">';
    echo '<h2>Raw Changelog Data for ID: ' . esc_html($changelog_id) . '</h2>';
    
    // Get all post meta
    $meta_keys = array(
        'changes_data',
        'changelog_block_data',
        'changelog_changes',
        'changelog_summary',
        'version_id'
    );
    
    foreach ($meta_keys as $key) {
        $value = get_post_meta($changelog_id, $key, true);
        echo '<h3>Meta Key: ' . esc_html($key) . '</h3>';
        
        if (empty($value)) {
            echo '<p>No data found.</p>';
        } else {
            echo '<pre style="background: #fff; padding: 10px; overflow: auto; max-height: 400px;">';
            echo esc_html(print_r($value, true));
            echo '</pre>';
        }
    }
    
    // Get the post itself
    $changelog_post = get_post($changelog_id);
    if ($changelog_post) {
        echo '<h3>Changelog Post</h3>';
        echo '<pre style="background: #fff; padding: 10px; overflow: auto; max-height: 400px;">';
        echo esc_html(print_r($changelog_post, true));
        echo '</pre>';
    }
    
    echo '</div>';
    
    return ob_get_clean();
}
add_shortcode('astp_raw_changelog_debug', 'astp_raw_changelog_debug_shortcode');

/**
 * Extract meaningful content from clarification blocks using block snapshot metadata
 * 
 * @param string|array $html The full HTML content of the clarification block or block data array
 * @param string $change_type The type of change ('added', 'removed', or 'amended')
 * @param array $previous_content Optional previous content for comparison in case of amendments
 * @return string|array The formatted content with appropriate highlighting or array of categorized changes
 */
function astp_extract_clarification_content($html, $change_type = '', $previous_content = null) {
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log("CLARIFICATION EXTRACTOR - Starting extraction with data type: " . gettype($html));
        if (is_array($html)) {
            error_log("CLARIFICATION EXTRACTOR - Array keys: " . implode(", ", array_keys($html)));
        }
    }
    
    $output = '';
    $extracted_text = '';
    $extracted_data = array(
        'heading' => '',
        'version' => '',
        'content' => '',
        'references' => array()
    );
    
    // If we have block data directly
    if (is_array($html) && isset($html['blockName']) && $html['blockName'] === 'uswds-gutenberg/clarification-block') {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("CLARIFICATION EXTRACTOR - Processing block data directly");
        }
        
        if (isset($html['attrs']['sections']) && is_array($html['attrs']['sections'])) {
            foreach ($html['attrs']['sections'] as $section) {
                if (isset($section['heading'])) {
                    $extracted_data['heading'] = $section['heading'];
                }
                if (isset($section['version'])) {
                    $extracted_data['version'] = $section['version'];
                }
                if (isset($section['content'])) {
                    // Preserve line breaks by converting <br> tags to newlines before stripping tags
                    $content = preg_replace('/<br\s*\/?>/i', "\n", $section['content']);
                    $extracted_data['content'] = strip_tags($content);
                }
                if (isset($section['standardReferences']) && is_array($section['standardReferences'])) {
                    foreach ($section['standardReferences'] as $ref) {
                        if (isset($ref['refName']) && isset($ref['title'])) {
                            $extracted_data['references'][] = array(
                                'name' => $ref['refName'],
                                'title' => $ref['title']
                            );
                        }
                    }
                }
            }
        }
    }
    // If we have HTML content
    else if (is_string($html)) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("CLARIFICATION EXTRACTOR - Processing HTML content");
        }
        
        // Try to extract JSON data from script tags
        $dom = new DOMDocument();
        @$dom->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        $scripts = $dom->getElementsByTagName('script');
        
        foreach ($scripts as $script) {
            if ($script->getAttribute('type') === 'application/json') {
                $json_data = json_decode($script->nodeValue, true);
                if ($json_data && isset($json_data['sections'])) {
                    foreach ($json_data['sections'] as $section) {
                        if (isset($section['heading'])) {
                            $extracted_data['heading'] = $section['heading'];
                        }
                        if (isset($section['version'])) {
                            $extracted_data['version'] = $section['version'];
                        }
                        if (isset($section['content'])) {
                            // Preserve line breaks by converting <br> tags to newlines before stripping tags
                            $content = preg_replace('/<br\s*\/?>/i', "\n", $section['content']);
                            $extracted_data['content'] = strip_tags($content);
                        }
                        if (isset($section['standardReferences']) && is_array($section['standardReferences'])) {
                            foreach ($section['standardReferences'] as $ref) {
                                if (isset($ref['refName']) && isset($ref['title'])) {
                                    $extracted_data['references'][] = array(
                                        'name' => $ref['refName'],
                                        'title' => $ref['title']
                                    );
                                }
                            }
                        }
                    }
                }
            }
        }
        
        // If no JSON data found, try to extract content from HTML structure
        if (empty($extracted_data['content'])) {
            $xpath = new DOMXPath($dom);
            $content_divs = $xpath->query("//div[contains(@class, 'usa-accordion__content')]//div[contains(@class, 'grid-col-7')]//div");
            
            foreach ($content_divs as $div) {
                // Get HTML content with line breaks preserved
                $html_content = $dom->saveHTML($div);
                // Convert <br> tags to newlines before stripping tags
                $content = preg_replace('/<br\s*\/?>/i', "\n", $html_content);
                $extracted_data['content'] .= strip_tags($content);
                break; // Just take the first content div
            }
        }
    }
    
    // Now format the output based on the extracted data and change type
    if (!empty($extracted_data['content'])) {
        // For amendments, we need to compare with previous content
        if ($change_type === 'amended' && !empty($previous_content)) {
            // If we have previous content for comparison, use text diff
            if (class_exists('\Text_Diff')) {
                $output = astp_format_text_diff($previous_content, $extracted_data['content']);
            } else {
                $output = astp_simple_text_diff($previous_content, $extracted_data['content']);
            }
        } else if ($change_type === 'added') {
            // For added content
            $output = '<mark class="added">' . $extracted_data['content'] . '</mark>';
        } else if ($change_type === 'removed') {
            // For removed content
            $output = '<mark class="removed">' . $extracted_data['content'] . '</mark>';
        } else {
            // No specific formatting for other change types
            $output = $extracted_data['content'];
        }
    }
    
    // Replace newlines with <br> tags but don't encode HTML
    $output = str_replace("\n", "<br />", $output);
    
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log("CLARIFICATION EXTRACTOR - Final output length: " . strlen($output));
        error_log("CLARIFICATION EXTRACTOR - Final output: " . (is_array($output) ? json_encode($output) : $output));
    }
    
    return $output;
}

/**
 * Simple text difference formatter when Text_Diff class is not available
 * 
 * @param string $old_text The previous version text
 * @param string $new_text The new version text
 * @param string $change_type The type of change (typically 'amended')
 * @return string|array Formatted HTML with appropriate markup or array of changes
 */
function astp_simple_text_diff($old_text, $new_text, $change_type = 'amended') {
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log("SIMPLE TEXT DIFF - Comparing text: " . substr($old_text, 0, 30) . "... with " . substr($new_text, 0, 30) . "...");
    }
    
    // Clean and normalize both texts
    $old_text = trim(str_replace(["\r\n", "\r"], "\n", $old_text));
    $new_text = trim(str_replace(["\r\n", "\r"], "\n", $new_text));
    
    // If texts are identical, just return the new text
    if ($old_text === $new_text) {
        return $new_text;
    }
    
    // Split texts into sentences and other tokens
    // We need to identify sentences and keep them intact, but treat punctuation as separate tokens
    $old_tokens = astp_tokenize_text($old_text);
    $new_tokens = astp_tokenize_text($new_text);
    
    // Find longest common subsequence of tokens
    $lcs = astp_find_word_diff_lcs($old_tokens, $new_tokens);
    
    // Map LCS indices to original arrays to identify changes
    $old_matched = array_fill(0, count($old_tokens), false);
    $new_matched = array_fill(0, count($new_tokens), false);
    
    foreach ($lcs as $match) {
        $old_matched[$match[0]] = true;
        $new_matched[$match[1]] = true;
    }
    
    // Check if this is a pure addition, removal, or amendment
    $all_old_matched = empty($old_tokens) || count(array_filter($old_matched)) == count($old_tokens);
    $all_new_matched = empty($new_tokens) || count(array_filter($new_matched)) == count($new_tokens);
    
    // Pure addition: All old text matches, but new text has additions
    if ($all_old_matched && !$all_new_matched) {
        $change_type = 'added';
    }
    // Pure removal: All new text matches, but old text had parts removed
    else if (!$all_old_matched && $all_new_matched) {
        $change_type = 'removed';
    }
    // Mixed changes: Both old and new have unmatched portions - this is an amendment
    else {
        $change_type = 'amended';
    }
    
    // Generate output with appropriate markup
    $in_mark = false;
    $result = '';
    
    for ($i = 0; $i < count($new_tokens); $i++) {
        $token = $new_tokens[$i];
        
        if (!$new_matched[$i]) {
            // This token is new/changed
            if (!$in_mark) {
                $result .= '<mark class="' . $change_type . '">';
                $in_mark = true;
            }
        } else {
            // This token is unchanged
            if ($in_mark) {
                $result .= '</mark>';
                $in_mark = false;
            }
        }
        
        // Always add the token (properly escaped with line breaks preserved)
        $safe_token = htmlspecialchars($token, ENT_NOQUOTES, 'UTF-8');
        $result .= str_replace("\n", "<br />", $safe_token);
    }
    
    // Close any open markup
    if ($in_mark) {
        $result .= '</mark>';
    }
    
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log("SIMPLE TEXT DIFF - Resulting HTML length: " . strlen($result));
    }
    
    return $result;
}

/**
 * Helper function to tokenize text for diffing
 * 
 * @param string $text The text to tokenize
 * @return array Array of tokens (sentences and punctuation)
 */
function astp_tokenize_text($text) {
    $tokens = array();
    
    // Split on sentence boundaries
    // This regex pattern identifies:
    // 1. Sentences ending with period, question mark, or exclamation point
    // 2. Line breaks as separate tokens
    // 3. Punctuation as separate tokens
    
    // First, mark newlines with a special placeholder so we can preserve them
    $text = str_replace("\n", "___NEWLINE___", $text);
    
    // Now split the text more carefully to separate:
    // - Sentences (ending with . ! ?)
    // - Punctuation marks as separate tokens
    // - Preserve newlines as distinct tokens
    
    // Split on sentence endings (period, question mark, exclamation point)
    $pattern = '/([^.!?]+[.!?]+)|\s+|([^\w\s])|___NEWLINE___/u';
    preg_match_all($pattern, $text, $matches);
    
    // Process all matches
    foreach ($matches[0] as $match) {
        if ($match === '___NEWLINE___') {
            $tokens[] = "\n"; // Restore the actual newline
        } else if (trim($match) !== '') {
            $tokens[] = $match;
        }
    }
    
    return $tokens;
}

/**
 * Find the longest common subsequence for word-level diffs
 *
 * @param array $a First array of tokens
 * @param array $b Second array of tokens
 * @return array Array of matching indices [i,j]
 */
function astp_find_word_diff_lcs($a, $b) {
    $lengths = array();
    
    // Initialize the length table
    for ($i = 0; $i <= count($a); $i++) {
        $lengths[$i][0] = 0;
    }
    
    for ($j = 0; $j <= count($b); $j++) {
        $lengths[0][$j] = 0;
    }
    
    // Fill the length table
    for ($i = 1; $i <= count($a); $i++) {
        for ($j = 1; $j <= count($b); $j++) {
            if ($a[$i-1] === $b[$j-1]) {
                $lengths[$i][$j] = $lengths[$i-1][$j-1] + 1;
            } else {
                $lengths[$i][$j] = max($lengths[$i-1][$j], $lengths[$i][$j-1]);
            }
        }
    }
    
    // Backtrack to find the actual matches
    $matches = array();
    $i = count($a);
    $j = count($b);
    
    while ($i > 0 && $j > 0) {
        if ($a[$i-1] === $b[$j-1]) {
            $matches[] = array($i-1, $j-1);
            $i--;
            $j--;
        } elseif ($lengths[$i-1][$j] >= $lengths[$i][$j-1]) {
            $i--;
        } else {
            $j--;
        }
    }
    
    // Return matches in correct order
    return array_reverse($matches);
}

/**
 * Format text differences using the WordPress Text_Diff class
 * 
 * @param string $old_text The previous version text
 * @param string $new_text The new version text
 * @return string Formatted HTML with appropriate markup
 */
function astp_format_text_diff($old_text, $new_text) {
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log("TEXT DIFF - Comparing text with WordPress Text_Diff");
    }
    
    // Make sure the Text_Diff class is loaded
    if (!class_exists('WP_Text_Diff_Renderer_Table')) {
        require_once ABSPATH . WPINC . '/wp-diff.php';
    }
    
    // Clean and normalize both texts
    $old_text = trim(str_replace(["\r\n", "\r"], "\n", $old_text));
    $new_text = trim(str_replace(["\r\n", "\r"], "\n", $new_text));
    
    // If texts are identical, just return the new text
    if ($old_text === $new_text) {
        return $new_text;
    }
    
    // Convert text to arrays of lines for the diff
    $old_lines = explode("\n", $old_text);
    $new_lines = explode("\n", $new_text);
    
    // Create the diff object
    $diff = new \Text_Diff('auto', array($old_lines, $new_lines));
    
    // Analyze the edits to determine the change type
    $change_type = 'amended';
    $edits = $diff->getEdits();
    
    $has_added = false;
    $has_removed = false;
    $has_changed = false;
    
    foreach ($edits as $edit) {
        if ($edit instanceof \Text_Diff_Op_add) {
            $has_added = true;
        } else if ($edit instanceof \Text_Diff_Op_delete) {
            $has_removed = true;
        } else if ($edit instanceof \Text_Diff_Op_change) {
            $has_changed = true;
        }
    }
    
    // Determine if this is a pure addition, removal or amendment
    if ($has_added && !$has_removed && !$has_changed) {
        $change_type = 'added';
    } else if (!$has_added && $has_removed && !$has_changed) {
        $change_type = 'removed';
    } else {
        $change_type = 'amended';
    }
    
    // Custom renderer for our specific needs
    $renderer_class = new class($change_type) extends \WP_Text_Diff_Renderer_Table {
        public $change_type;
        
        public function __construct($change_type) {
            parent::__construct(array(
                'show_split_view' => false,
            ));
            $this->change_type = $change_type;
        }
        
        // Override the rendering to use our mark classes
        public function _added($lines) {
            $r = '';
            foreach ($lines as $line) {
                // Use htmlspecialchars instead of esc_html to better control encoding
                $r .= '<mark class="' . $this->change_type . '">' . htmlspecialchars($line, ENT_NOQUOTES, 'UTF-8') . '</mark> ';
            }
            return $r;
        }
        
        public function _deleted($lines) {
            if ($this->change_type == 'removed') {
                $r = '';
                foreach ($lines as $line) {
                    // Use htmlspecialchars instead of esc_html to better control encoding
                    $r .= '<mark class="removed">' . htmlspecialchars($line, ENT_NOQUOTES, 'UTF-8') . '</mark> ';
                }
                return $r;
            }
            return '';
        }
        
        public function _context($lines) {
            $r = '';
            foreach ($lines as $line) {
                // Use htmlspecialchars instead of esc_html to better control encoding
                $r .= htmlspecialchars($line, ENT_NOQUOTES, 'UTF-8') . ' ';
            }
            return $r;
        }
        
        public function _changed($orig, $final) {
            $r = '';
            // Changed lines are both removed and added with different classes
            foreach ($final as $line) {
                // Use htmlspecialchars instead of esc_html to better control encoding
                $r .= '<mark class="amended">' . htmlspecialchars($line, ENT_NOQUOTES, 'UTF-8') . '</mark> ';
            }
            return $r;
        }
    };
    
    // Render the diff
    $output = $renderer_class->render($diff);
    
    // Clean up the output to remove table structure but keep the highlighted content
    $output = preg_replace('/<td.*?>/', '', $output);
    $output = str_replace(array('</td>', '<tr>', '</tr>', '<table class="diff">', '</table>'), '', $output);
    
    // Convert newlines to <br> tags to preserve line breaks in HTML output
    $output = str_replace("\n", "<br />", $output);
    
    return $output;
} 
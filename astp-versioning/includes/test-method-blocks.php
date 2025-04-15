<?php
/**
 * Test Method Specialized Blocks
 *
 * @package ASTP_Versioning
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register Test Method blocks
 */
function astp_register_test_method_blocks() {
    // Skip if Gutenberg is not available
    if (!function_exists('register_block_type')) {
        return;
    }

    // Register block script - moved from here to astp-versioning.php to ensure it's loaded in editor

    // Pass data to JavaScript - also moved to main file

    // Register Title Section Block (Available for both CCG and TP)
    register_block_type('astp/title-section', array(
        'editor_script' => 'astp-test-method-blocks',
        'editor_style' => 'astp-test-method-blocks-editor',
        'style' => 'astp-test-method-blocks',
        'render_callback' => 'astp_render_title_section_block',
        'attributes' => array(
            'documentTitle' => array('type' => 'string'),
            'documentNumber' => array('type' => 'string'),
            'documentDate' => array('type' => 'string'),
            'content' => array('type' => 'string')
        ),
        'category' => 'astp-test-method'
    ));

    // Register CCG Revision History Block
    register_block_type('astp/ccg-revision-history', array(
        'editor_script' => 'astp-test-method-blocks',
        'editor_style' => 'astp-test-method-blocks-editor',
        'style' => 'astp-test-method-blocks',
        'render_callback' => 'astp_render_ccg_revision_history_block',
        'attributes' => array(
            'content' => array('type' => 'string'),
            'showAllHistory' => array('type' => 'boolean', 'default' => true),
            'limit' => array('type' => 'integer', 'default' => 5)
        ),
        'category' => 'astp-ccg'
    ));

    // Register TP Revision History Block
    register_block_type('astp/tp-revision-history', array(
        'editor_script' => 'astp-test-method-blocks',
        'editor_style' => 'astp-test-method-blocks-editor',
        'style' => 'astp-test-method-blocks',
        'render_callback' => 'astp_render_tp_revision_history_block',
        'attributes' => array(
            'content' => array('type' => 'string'),
            'showAllHistory' => array('type' => 'boolean', 'default' => true),
            'limit' => array('type' => 'integer', 'default' => 5)
        ),
        'category' => 'astp-tp'
    ));

    // Keep the legacy revision history block for backward compatibility
    register_block_type('astp/revision-history', array(
        'editor_script' => 'astp-test-method-blocks',
        'editor_style' => 'astp-test-method-blocks-editor',
        'style' => 'astp-test-method-blocks',
        'render_callback' => 'astp_render_revision_history_block',
        'attributes' => array(
            'content' => array('type' => 'string'),
            'showAllHistory' => array('type' => 'boolean', 'default' => true),
            'documentType' => array('type' => 'string', 'default' => '')
        ),
        'category' => 'astp-test-method'
    ));

    // Add debug log - this will be visible in the browser console when debugging is enabled
    error_log('ASTP TEST METHOD BLOCKS: Registered ' . count(WP_Block_Type_Registry::get_instance()->get_all_registered()) . ' blocks total.');
    
    // Check for our specific blocks
    $registry = WP_Block_Type_Registry::get_instance();
    $our_blocks = array(
        'astp/title-section',
        'astp/ccg-revision-history',
        'astp/tp-revision-history',
        'astp/revision-history'
    );
    
    foreach ($our_blocks as $block_name) {
        if ($registry->is_registered($block_name)) {
            error_log('ASTP TEST METHOD BLOCKS: Successfully registered ' . $block_name);
        } else {
            error_log('ASTP TEST METHOD BLOCKS: Failed to register ' . $block_name);
        }
    }
}
add_action('init', 'astp_register_test_method_blocks');

/**
 * Render Title Section Block
 */
function astp_render_title_section_block($attributes, $content) {
    $document_title = isset($attributes['documentTitle']) ? sanitize_text_field($attributes['documentTitle']) : '';
    $document_number = isset($attributes['documentNumber']) ? sanitize_text_field($attributes['documentNumber']) : '';
    $document_date = isset($attributes['documentDate']) ? sanitize_text_field($attributes['documentDate']) : '';
    $block_content = isset($attributes['content']) ? wp_kses_post($attributes['content']) : '';
    
    ob_start();
    ?>
    <div class="astp-title-section" data-section-type="title">
        <div class="astp-document-header">
            <?php if (!empty($document_title)) : ?>
                <h1 class="astp-document-title"><?php echo esc_html($document_title); ?></h1>
            <?php endif; ?>
            
            <div class="astp-document-info">
                <?php if (!empty($document_number)) : ?>
                    <div class="astp-document-number">Document #: <?php echo esc_html($document_number); ?></div>
                <?php endif; ?>
                
                <?php if (!empty($document_date)) : ?>
                    <div class="astp-document-date">Date: <?php echo esc_html($document_date); ?></div>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if (!empty($block_content)) : ?>
            <div class="astp-document-introduction">
                <?php echo $block_content; ?>
            </div>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Render the Revision History block (Legacy)
 */
function astp_render_revision_history_block($attributes) {
    // Ensure attributes is an array
    if (!is_array($attributes)) {
        $attributes = [];
    }
    
    // Check for document type
    $document_type = isset($attributes['documentType']) ? sanitize_text_field($attributes['documentType']) : '';
    
    // If a specific document type is specified, redirect to the appropriate dedicated block
    if (!empty($document_type) && in_array($document_type, ['ccg', 'tp'])) {
        return astp_render_single_document_history(get_the_ID(), $document_type, $attributes);
    }
    
    // Show a message recommending the new blocks and display both document types
    $output = '<div class="astp-revision-history astp-full-history">';
    
    if (is_admin() || current_user_can('edit_posts')) {
        $output .= '<div class="astp-block-notice" style="background-color: #f8f9fa; border-left: 4px solid #007cba; padding: 10px 15px; margin-bottom: 20px;">
            <p><strong>Note to editors:</strong> This is the legacy version history block. For better organization, please consider using the dedicated <strong>CCG Revision History</strong> and <strong>TP Revision History</strong> blocks instead.</p>
        </div>';
    }
    
    // First render CCG section
    $ccg_output = astp_render_single_document_history(get_the_ID(), 'ccg', $attributes, false);
    
    // Then render TP section
    $tp_output = astp_render_single_document_history(get_the_ID(), 'tp', $attributes, false);
    
    // Combine outputs
    $output .= $ccg_output . $tp_output;
    
    // Add filtering JavaScript for both sections
    $output .= '<script>
        document.addEventListener("DOMContentLoaded", function() {
            const filterButtons = document.querySelectorAll(".astp-filter-button");
            
            filterButtons.forEach(function(button) {
                button.addEventListener("click", function() {
                    const filter = this.getAttribute("data-filter");
                    const section = this.closest(".astp-document-history-section");
                    
                    // Update button active state in this section only
                    section.querySelectorAll(".astp-filter-button").forEach(btn => btn.classList.remove("active"));
                    this.classList.add("active");
                    
                    // Get all change groups in this section
                    const changeGroups = section.querySelectorAll(".astp-change-group");
                    
                    if (filter === "all") {
                        // Show all groups
                        changeGroups.forEach(group => group.style.display = "block");
                    } else {
                        // Hide all groups first
                        changeGroups.forEach(group => group.style.display = "none");
                        
                        // Show only filtered groups
                        section.querySelectorAll(".astp-change-group-" + filter).forEach(group => {
                            group.style.display = "block";
                        });
                    }
                });
            });
        });
    </script>';
    
    $output .= '</div>'; // .astp-revision-history
    
    return $output;
}

/**
 * Render a single document version history
 *
 * @param int    $post_id      Post ID of the test method
 * @param string $document_type Document type (ccg or tp)
 * @param array  $attributes   Block attributes
 * @param bool   $standalone   Whether this is a standalone block (true) or part of combined history (false)
 * @return string HTML output
 */
function astp_render_single_document_history($post_id, $document_type, $attributes, $standalone = true) {
    $output = '';
    
    // Get version history for the document type
    $args = array(
        'post_type' => 'astp_version',
        'posts_per_page' => -1,
        'meta_query' => array(
            array(
                'key' => 'parent_test_method',
                'value' => $post_id,
                'compare' => '='
            ),
            array(
                'key' => 'document_type',
                'value' => $document_type,
                'compare' => '='
            )
        ),
        'orderby' => 'meta_value',
        'meta_key' => 'version_number',
        'order' => 'DESC'
    );
    
    $versions_query = new WP_Query($args);
    $versions = $versions_query->posts;
    
    if (empty($versions)) {
        if ($standalone) {
            return '<div class="empty-history">No version history available for this document type.</div>';
        } else {
            return '';
        }
    }
    
    // Get document type label
    $type_label = $document_type === 'ccg' ? 'Certification Companion Guide' : 'Test Procedure';
    $type_class = $document_type;
    
    // Start the revision history HTML
    if ($standalone) {
        $output .= '<div class="astp-revision-history">';
        $output .= '<h3 class="astp-revision-history-title astp-' . esc_attr($type_class) . '-title">Version History (' . esc_html($type_label) . ')</h3>';
    }
    
    $output .= '<div class="astp-versions-list astp-' . esc_attr($type_class) . '-versions">';
    
    foreach ($versions as $index => $version) {
        $version_id = $version->ID;
        $version_number = get_post_meta($version_id, 'version_number', true);
        $version_date = get_post_meta($version_id, 'release_date', true);
        $version_type = get_post_meta($version_id, 'version_type', true);
        $version_notes = get_post_meta($version_id, 'version_notes', true);
        $is_latest = ($index === 0);
        
        // Skip versions without a number
        if (empty($version_number)) {
            continue;
        }
        
        // Convert date format
        $formatted_date = !empty($version_date) ? date_i18n(get_option('date_format'), strtotime($version_date)) : '';
        
        // Version CSS classes
        $version_classes = array(
            'astp-version-entry',
            'astp-document-' . $document_type
        );
        
        if ($is_latest) {
            $version_classes[] = 'astp-latest-version';
        }
        
        if ($version_type === 'hotfix') {
            $version_classes[] = 'astp-hotfix-version';
        }
        
        $output .= '<div class="' . esc_attr(implode(' ', $version_classes)) . '">';
        
        // Version header with badge
        $output .= '<div class="astp-version-header">';
        $output .= '<h4 class="astp-version-title">Version ' . esc_html($version_number);
        
        // Add document type badge
        $output .= ' <span class="astp-document-type-badge astp-' . esc_attr($document_type) . '-badge">' . esc_html($type_label) . '</span>';
        
        if ($is_latest) {
            $output .= ' <span class="astp-version-label astp-latest-label">Latest</span>';
        }
        
        if ($version_type === 'hotfix') {
            $output .= ' <span class="astp-version-label astp-hotfix-label">Hotfix</span>';
        }
        
        $output .= '</h4>';
        
        if (!empty($formatted_date)) {
            $output .= '<div class="astp-version-date">' . esc_html($formatted_date) . '</div>';
        }
        
        $output .= '</div>'; // End version header
        
        // Version content
        $output .= '<div class="astp-version-content">';
        
        // Get changes for this version
        if ($index < count($versions) - 1) {
            $previous_version_id = $versions[$index + 1]->ID;
            
            // Display version changes
            $changes_html = astp_display_version_changes($version_id, $previous_version_id);
            
            if (!empty($changes_html)) {
                $output .= '<div class="astp-version-changes">';
                $output .= '<h5>Changes from Previous Version:</h5>';
                $output .= $changes_html;
                $output .= '</div>';
            }
        }
        
        // Version notes
        if (!empty($version_notes)) {
            $output .= '<div class="astp-version-notes">';
            $output .= '<h5>Notes:</h5>';
            $output .= wpautop($version_notes);
            $output .= '</div>';
        }
        
        $output .= '</div>'; // End version content
        
        $output .= '</div>'; // End version entry
    }
    
    $output .= '</div>'; // End versions list
    
    if ($standalone) {
        $output .= '</div>'; // End revision history container
    }
    
    return $output;
}

/**
 * Register hook to track specialized blocks when saving posts
 */
function astp_track_specialized_blocks_on_save($post_id, $post) {
    // Only run this on our post type
    if (!isset($post->post_type) || $post->post_type !== 'astp_test_method') {
        return;
    }
    
    // Skip autosaves
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    
    // Check if this is a version in development
    $dev_version_id = get_post_meta($post_id, 'in_development_version', true);
    
    if (!$dev_version_id) {
        return;
    }
    
    // Parse the post content to extract our specialized blocks
    $blocks = parse_blocks($post->post_content);
    
    if (empty($blocks)) {
        return;
    }
    
    // Get current content snapshot
    $snapshot = array();
    
    foreach ($blocks as $block) {
        // Only process our specialized blocks with valid blockName
        if (isset($block['blockName']) && 
            is_string($block['blockName']) && 
            $block['blockName'] !== null && 
            strpos($block['blockName'], 'astp/') === 0) {
            
            $section_type = str_replace('astp/', '', $block['blockName']);
            
            $snapshot[$section_type] = array(
                'blockName' => $block['blockName'],
                'attrs' => isset($block['attrs']) && is_array($block['attrs']) ? $block['attrs'] : array(),
                'innerHTML' => isset($block['innerHTML']) && is_string($block['innerHTML']) ? $block['innerHTML'] : '',
                'innerContent' => isset($block['innerContent']) && is_array($block['innerContent']) ? $block['innerContent'] : array()
            );
        }
    }
    
    // Store the snapshot if we have specialized blocks
    if (!empty($snapshot)) {
        update_post_meta($dev_version_id, 'blocks_snapshot', $snapshot);
    }
}
add_action('save_post', 'astp_track_specialized_blocks_on_save', 10, 2);

/**
 * Add Test Method block category
 * 
 * Note: This functionality has been moved to block-setup.php to ensure it runs earlier
 * in the initialization process. The code here is kept for reference.
 */
// function astp_add_test_method_block_category($categories, $post) {
//     return array_merge(
//         $categories,
//         array(
//             array(
//                 'slug'  => 'astp-test-method',
//                 'title' => __('Test Method Sections', 'astp-versioning'),
//                 'icon'  => 'clipboard',
//             ),
//         )
//     );
// }

// WordPress 5.8+ uses block_categories_all
// if (function_exists('get_default_block_categories')) {
//     add_filter('block_categories_all', 'astp_add_test_method_block_category', 10, 2);
// } else {
//     // Older WordPress versions use block_categories
//     add_filter('block_categories', 'astp_add_test_method_block_category', 10, 2);
// } 

/**
 * Generate and display version changes
 *
 * @param int $version_id The version ID
 * @param int $previous_id Previous version ID to compare against
 * @return string HTML output of changes
 */
function astp_display_version_changes($version_id, $previous_id) {
    // Get block snapshots
    $current_snapshots = get_post_meta($version_id, 'block_snapshots', true);
    $previous_snapshots = get_post_meta($previous_id, 'block_snapshots', true);
    
    // If snapshots not found, return empty
    if (empty($current_snapshots) || empty($previous_snapshots)) {
        return '<p>Content structure changed, detailed comparison not available.</p>';
    }
    
    // Compare snapshots
    $changes = astp_compare_block_snapshots($current_snapshots, $previous_snapshots);
    $has_changes = !empty($changes['added']) || !empty($changes['removed']) || !empty($changes['amended']);
    
    if (!$has_changes) {
        return '<p>No significant content changes detected.</p>';
    }
    
    $output = '';
    
    // Handle added blocks
    if (!empty($changes['added'])) {
        $output .= '<div class="astp-change-section astp-added-blocks">';
        $output .= '<h6>Added Content:</h6>';
        $output .= '<ul>';
        
        foreach ($changes['added'] as $change) {
            $block = $change['block'];
            $section = $change['section'];
            
            $output .= '<li>';
            
            // Determine block type for better labeling
            $block_type = '';
            if (isset($block['blockName'])) {
                // Include both legacy and USWDS blocks
                switch ($block['blockName']) {
                    case 'astp/regulation-text':
                    case 'uswds-gutenberg/regulatory-block':
                        $block_type = 'Regulatory Text';
                        break;
                    case 'astp/standards-referenced':
                    case 'uswds-gutenberg/standards-block':
                        $block_type = 'Standards Referenced';
                        break;
                    case 'astp/update-deadlines':
                        $block_type = 'Required Update Deadlines';
                        break;
                    case 'astp/certification-dependencies':
                    case 'uswds-gutenberg/dependencies-block':
                        $block_type = 'Certification Dependencies';
                        break;
                    case 'astp/technical-explanations':
                    case 'uswds-gutenberg/clarification-block':
                        $block_type = 'Technical Explanations';
                        break;
                    case 'uswds-gutenberg/resources-block':
                        $block_type = 'Resources';
                        break;
                    case 'uswds-gutenberg/testing-components':
                        $block_type = 'Testing Components';
                        break;
                    case 'uswds-gutenberg/testing-steps-block':
                        $block_type = 'Testing Steps';
                        break;
                    case 'uswds-gutenberg/testing-tools-block':
                        $block_type = 'Testing Tools';
                        break;
                    default:
                        $block_type = str_replace('astp/', '', $block['blockName']);
                        $block_type = str_replace('uswds-gutenberg/', '', $block_type);
                        $block_type = str_replace('-', ' ', $block_type);
                        $block_type = ucwords($block_type);
                }
            }
            
            if (!empty($block_type)) {
                $output .= '<strong>' . esc_html($block_type) . '</strong>: ';
            }
            
            if (!empty($section['full_text'])) {
                $output .= esc_html($section['full_text']);
            } else {
                $output .= 'New content added';
            }
            
            $output .= '</li>';
        }
        
        $output .= '</ul>';
        $output .= '</div>';
    }
    
    // Handle removed blocks
    if (!empty($changes['removed'])) {
        $output .= '<div class="astp-change-section astp-removed-blocks">';
        $output .= '<h6>Removed Content:</h6>';
        $output .= '<ul>';
        
        foreach ($changes['removed'] as $change) {
            $block = $change['block'];
            $section = $change['section'];
            
            $output .= '<li>';
            
            // Determine block type for better labeling
            $block_type = '';
            if (isset($block['blockName'])) {
                // Include both legacy and USWDS blocks
                switch ($block['blockName']) {
                    case 'astp/regulation-text':
                    case 'uswds-gutenberg/regulatory-block':
                        $block_type = 'Regulatory Text';
                        break;
                    case 'astp/standards-referenced':
                    case 'uswds-gutenberg/standards-block':
                        $block_type = 'Standards Referenced';
                        break;
                    case 'astp/update-deadlines':
                        $block_type = 'Required Update Deadlines';
                        break;
                    case 'astp/certification-dependencies':
                    case 'uswds-gutenberg/dependencies-block':
                        $block_type = 'Certification Dependencies';
                        break;
                    case 'astp/technical-explanations':
                    case 'uswds-gutenberg/clarification-block':
                        $block_type = 'Technical Explanations';
                        break;
                    case 'uswds-gutenberg/resources-block':
                        $block_type = 'Resources';
                        break;
                    case 'uswds-gutenberg/testing-components':
                        $block_type = 'Testing Components';
                        break;
                    case 'uswds-gutenberg/testing-steps-block':
                        $block_type = 'Testing Steps';
                        break;
                    case 'uswds-gutenberg/testing-tools-block':
                        $block_type = 'Testing Tools';
                        break;
                    default:
                        $block_type = str_replace('astp/', '', $block['blockName']);
                        $block_type = str_replace('uswds-gutenberg/', '', $block_type);
                        $block_type = str_replace('-', ' ', $block_type);
                        $block_type = ucwords($block_type);
                }
            }
            
            if (!empty($block_type)) {
                $output .= '<strong>' . esc_html($block_type) . '</strong>: ';
            }
            
            if (!empty($section['full_text'])) {
                $output .= esc_html($section['full_text']);
            } else {
                $output .= 'Content removed';
            }
            
            $output .= '</li>';
        }
        
        $output .= '</ul>';
        $output .= '</div>';
    }
    
    // Handle amended blocks
    if (!empty($changes['amended'])) {
        $output .= '<div class="astp-change-section astp-amended-blocks">';
        $output .= '<h6>Modified Content:</h6>';
        $output .= '<ul>';
        
        foreach ($changes['amended'] as $change) {
            $old_block = $change['old_block'];
            $new_block = $change['new_block'];
            $diff = $change['diff'];
            $section = $change['section'];
            
            $output .= '<li>';
            
            // Determine block type for better labeling
            $block_type = '';
            if (isset($new_block['blockName'])) {
                // Include both legacy and USWDS blocks
                switch ($new_block['blockName']) {
                    case 'astp/regulation-text':
                    case 'uswds-gutenberg/regulatory-block':
                        $block_type = 'Regulatory Text';
                        break;
                    case 'astp/standards-referenced':
                    case 'uswds-gutenberg/standards-block':
                        $block_type = 'Standards Referenced';
                        break;
                    case 'astp/update-deadlines':
                        $block_type = 'Required Update Deadlines';
                        break;
                    case 'astp/certification-dependencies':
                    case 'uswds-gutenberg/dependencies-block':
                        $block_type = 'Certification Dependencies';
                        break;
                    case 'astp/technical-explanations':
                    case 'uswds-gutenberg/clarification-block':
                        $block_type = 'Technical Explanations';
                        break;
                    case 'uswds-gutenberg/resources-block':
                        $block_type = 'Resources';
                        break;
                    case 'uswds-gutenberg/testing-components':
                        $block_type = 'Testing Components';
                        break;
                    case 'uswds-gutenberg/testing-steps-block':
                        $block_type = 'Testing Steps';
                        break;
                    case 'uswds-gutenberg/testing-tools-block':
                        $block_type = 'Testing Tools';
                        break;
                    default:
                        $block_type = str_replace('astp/', '', $new_block['blockName']);
                        $block_type = str_replace('uswds-gutenberg/', '', $block_type);
                        $block_type = str_replace('-', ' ', $block_type);
                        $block_type = ucwords($block_type);
                }
            }
            
            if (!empty($block_type)) {
                $output .= '<strong>' . esc_html($block_type) . '</strong>: ';
            }
            
            if (!empty($section['full_text'])) {
                $output .= esc_html($section['full_text']) . ' ';
            }
            
            // Check if there's a nice tabular diff
            if (!empty($diff['table_html'])) {
                $output .= $diff['table_html'];
            } elseif (!empty($diff['attribute_changes']) || !empty($diff['html_diff'])) {
                // Display summary of changes
                $output .= '<div class="astp-changes-details">';
                
                if (!empty($diff['attribute_changes'])) {
                    foreach ($diff['attribute_changes'] as $attr_change) {
                        $output .= '<p><strong>' . esc_html($attr_change['name']) . '</strong> changed from "' . 
                                   esc_html($attr_change['old']) . '" to "' . esc_html($attr_change['new']) . '"</p>';
                    }
                }
                
                if (!empty($diff['html_diff'])) {
                    $output .= $diff['html_diff'];
                }
                
                $output .= '</div>';
            } else {
                $output .= '<p>Content modified</p>';
            }
            
            $output .= '</li>';
        }
        
        $output .= '</ul>';
        $output .= '</div>';
    }
    
    return $output;
}

/**
 * Render the CCG Revision History block
 */
function astp_render_ccg_revision_history_block($attributes) {
    // Ensure attributes is an array
    if (!is_array($attributes)) {
        $attributes = [];
    }
    
    // Force the document type to be CCG
    $attributes['documentType'] = 'ccg';
    
    // Use the single document history renderer
    return astp_render_single_document_history(get_the_ID(), 'ccg', $attributes, true);
}

/**
 * Render the TP Revision History block
 */
function astp_render_tp_revision_history_block($attributes) {
    // Ensure attributes is an array
    if (!is_array($attributes)) {
        $attributes = [];
    }
    
    // Force the document type to be TP
    $attributes['documentType'] = 'tp';
    
    // Use the single document history renderer
    return astp_render_single_document_history(get_the_ID(), 'tp', $attributes, true);
} 
<?php
/**
 * Version Creation & Management Functions
 *
 * @package ASTP_Versioning
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Creates a new version of a Test Method
 *
 * @param int    $post_id      The ID of the Test Method post
 * @param string $version_type The type of version to create ('major', 'minor', or 'hotfix')
 * @param string $document_type The type of document ('ccg' or 'tp')
 * @return int|bool The ID of the new version post, or false on failure
 */
function astp_create_new_version($post_id, $version_type = 'minor', $document_type = null) {
    // Get the post content
    $post = get_post($post_id);
    
    if (!$post || 'astp_test_method' !== $post->post_type) {
        return false;
    }
    
    // Get current published version based on document type if provided
    if ($document_type) {
        $current_version_id = get_post_meta($post_id, "{$document_type}_current_published_version", true);
    } else {
    $current_version_id = get_post_meta($post_id, 'current_published_version', true);
    }
    
    // Determine new version number
    $new_version_number = astp_calculate_new_version_number($current_version_id, $version_type);
    
    // Create new version post
    $version_post_id = wp_insert_post([
        'post_title' => $post->post_title . ' - v' . $new_version_number,
        'post_type' => 'astp_version',
        'post_status' => 'publish',
        'post_parent' => $post_id,
    ]);
    
    if (!is_wp_error($version_post_id)) {
        // Store version metadata
        update_post_meta($version_post_id, 'version_number', $new_version_number);
        update_post_meta($version_post_id, 'release_date', date('Y-m-d'));
        update_post_meta($version_post_id, 'version_type', $version_type);
        update_post_meta($version_post_id, 'previous_version_id', $current_version_id);
        
        // Store current content snapshot
        $content = $post->post_content;
        update_post_meta($version_post_id, 'content_snapshot', $content);
        
        // Store information about the test method parent
        update_post_meta($version_post_id, 'parent_test_method', $post_id);
        
        // Set document type if provided
        if ($document_type) {
            update_post_meta($version_post_id, 'document_type', $document_type);
        }
        
        // Parse content and store block snapshots
        $blocks = parse_blocks($post->post_content);
        astp_store_block_snapshots($version_post_id, $blocks, $document_type);
        
        // Set version status
        wp_set_object_terms($version_post_id, 'in-development', 'version_status');
        wp_set_object_terms($version_post_id, $version_type, 'version_type');
        
        // Set document type if provided
        if ($document_type !== null) {
            wp_set_object_terms($version_post_id, $document_type, 'document_type');
            
            // Store document type as post meta for easier access
            update_post_meta($version_post_id, 'document_type', $document_type);
            
            // Track document type specific version
            $key = $document_type . '_in_development_version';
            update_post_meta($post_id, $key, $version_post_id);
        } else {
            // Update parent post references - legacy approach
        update_post_meta($post_id, 'in_development_version', $version_post_id);
        }
        
        // Add version to history list
        $versions = get_post_meta($post_id, 'versions_history', true) ?: [];
        $versions[] = $version_post_id;
        update_post_meta($post_id, 'versions_history', $versions);
        
        // Also track in document-specific history
        if ($document_type !== null) {
            $doc_versions = get_post_meta($post_id, $document_type . '_versions_history', true) ?: [];
            $doc_versions[] = $version_post_id;
            update_post_meta($post_id, $document_type . '_versions_history', $doc_versions);
        }
        
        // Log the version creation
        astp_log_version_event($version_post_id, 'created');
        
        return $version_post_id;
    }
    
    return false;
}

/**
 * Creates a hotfix version for a test method
 *
 * @param int    $version_id    The ID of the version post to hotfix
 * @param string $document_type Optional document type (ccg or tp)
 * @return int|bool The ID of the new hotfix version post, or false on failure
 */
function astp_create_hotfix_version($version_id, $document_type = null) {
    // Get the version post
    $version_post = get_post($version_id);
    
    if (!$version_post || 'astp_version' !== $version_post->post_type) {
        return false;
    }
    
    // Get the parent test method
    $parent_post_id = wp_get_post_parent_id($version_id);
    
    if (!$parent_post_id) {
        return false;
    }
    
    $parent_post = get_post($parent_post_id);
    
    if (!$parent_post || 'astp_test_method' !== $parent_post->post_type) {
        return false;
    }
    
    // Get version number to create hotfix from
    $version_number = get_post_meta($version_id, 'version_number', true);
    
    if (!$version_number) {
        return false;
    }
    
    // Create hotfix version number (add .1, .2, etc.)
    $parts = explode('.', $version_number);
    $major = (int)$parts[0];
    $minor = isset($parts[1]) ? (int)$parts[1] : 0;
    
    // Existing hotfixes for this version
    $existing_hotfixes = get_post_meta($version_id, 'hotfix_versions', true);
    
    if (!is_array($existing_hotfixes)) {
        $existing_hotfixes = array();
    }
    
    $hotfix_number = count($existing_hotfixes) + 1;
    $new_version_number = $major . '.' . $minor . '.' . $hotfix_number;
    
    // Get content to use for this hotfix 
    $content = get_post_meta($version_id, 'content_snapshot', true);
    
    if (empty($content)) {
        // Fallback to parent content if version has no snapshot
        $content = $parent_post->post_content;
    }
    
    // Create a new version post
    $hotfix_post_id = wp_insert_post(array(
        'post_title'    => sprintf('Version %s', $new_version_number),
        'post_content'  => $content,
        'post_status'   => 'publish',
        'post_type'     => 'astp_version',
        'post_parent'   => $parent_post_id,
        'post_author'   => get_current_user_id(),
        'menu_order'    => 0,
    ));
    
    if (is_wp_error($hotfix_post_id)) {
        return false;
    }
    
    // Store metadata about the hotfix
        update_post_meta($hotfix_post_id, 'version_number', $new_version_number);
        update_post_meta($hotfix_post_id, 'release_date', date('Y-m-d'));
        update_post_meta($hotfix_post_id, 'is_hotfix_for', $version_id);
        
    // Set version type to 'hotfix'
        wp_set_object_terms($hotfix_post_id, 'hotfix', 'version_type');
        
    // Set status to 'in development'
    wp_set_object_terms($hotfix_post_id, 'in_development', 'version_status');
    
    // Update the original version with reference to this hotfix
        $existing_hotfixes[] = $hotfix_post_id;
        update_post_meta($version_id, 'hotfix_versions', $existing_hotfixes);
        
    // Get document type from the version being hotfixed if not provided
    if (!$document_type) {
        $document_type = get_post_meta($version_id, 'document_type', true);
    }
    
    // Set document type on the hotfix version
    if (!empty($document_type)) {
        update_post_meta($hotfix_post_id, 'document_type', $document_type);
        
        // Update in_development_version reference based on document type
        update_post_meta($parent_post_id, "{$document_type}_in_development_version", $hotfix_post_id);
    } else {
        // Legacy approach
        update_post_meta($parent_post_id, 'in_development_version', $hotfix_post_id);
    }
    
    // Take a snapshot of the content
    update_post_meta($hotfix_post_id, 'content_snapshot', $content);
    
    // Parse blocks and store block snapshots
    $blocks = parse_blocks($content);
    astp_store_block_snapshots($hotfix_post_id, $blocks, $document_type);
        
        // Log the hotfix creation
        astp_log_version_event($hotfix_post_id, 'hotfix_created');
        
        return $hotfix_post_id;
}

/**
 * Store block snapshots for version comparison
 *
 * @param int    $version_id    The version post ID
 * @param array  $blocks        Array of parsed blocks
 * @param string $document_type Optional document type to filter blocks (ccg or tp)
 */
function astp_store_block_snapshots($version_id, $blocks, $document_type = null) {
    $block_snapshots = [];
    
    foreach ($blocks as $index => $block) {
        // Skip empty blocks
        if (empty($block['blockName'])) {
            continue;
        }
        
        // If document type is specified, filter blocks by type
        if (!empty($document_type)) {
            $block_name = $block['blockName'];
            
            // Determine if this block should be included based on document type
            $include_block = false;
            
            // Blocks that are always included regardless of document type
            $common_blocks = ['astp/title-section'];
            
            if (in_array($block_name, $common_blocks)) {
                $include_block = true;
            }
            // CCG specific blocks - legacy blocks and new USWDS blocks
            else if ($document_type === 'ccg' && (
                // Legacy CCG blocks
                $block_name === 'astp/regulation-text' ||
                $block_name === 'astp/standards-referenced' ||
                $block_name === 'astp/update-deadlines' ||
                $block_name === 'astp/certification-dependencies' ||
                $block_name === 'astp/ccg-revision-history' ||
                // New USWDS CCG blocks
                $block_name === 'uswds-gutenberg/clarification-block' ||
                $block_name === 'uswds-gutenberg/regulatory-block' ||
                $block_name === 'uswds-gutenberg/standards-block' ||
                $block_name === 'uswds-gutenberg/dependencies-block' ||
                $block_name === 'uswds-gutenberg/resources-block'
            )) {
                $include_block = true;
            }
            // TP specific blocks - legacy blocks and new USWDS blocks
            else if ($document_type === 'tp' && (
                // Legacy TP blocks
                $block_name === 'astp/technical-explanations' ||
                $block_name === 'astp/tp-revision-history' ||
                // New USWDS TP blocks
                $block_name === 'uswds-gutenberg/testing-components' ||
                $block_name === 'uswds-gutenberg/testing-steps-block' ||
                $block_name === 'uswds-gutenberg/testing-tools-block'
            )) {
                $include_block = true;
            }
            
            // Skip this block if it's not relevant for the document type
            if (!$include_block) {
                error_log("STORE BLOCKS: Skipping block {$block_name} - not relevant for {$document_type}");
                continue;
            }
            
            error_log("STORE BLOCKS: Including block {$block_name} for {$document_type}");
        }
        
        // Store block with its index
        $block_snapshots[$index] = $block;
    }
    
    update_post_meta($version_id, 'block_snapshots', $block_snapshots);
}

/**
 * Compare block snapshots to identify changes
 * 
 * @param array $new_snapshots The new block snapshots
 * @param array $old_snapshots The old block snapshots
 * @return array Changes categorized as added, removed, and amended
 */
function astp_compare_block_snapshots($new_snapshots, $old_snapshots) {
    $changes = array(
        'added' => array(),
        'removed' => array(),
        'amended' => array(),
        'sections_order' => array(), // To track the order of sections
        'raw_snapshots' => array(    // Store raw snapshots for debugging
            'new' => $new_snapshots,
            'old' => $old_snapshots
        )
    );
    
    // Track all sections seen to maintain document order
    $sections_seen = array();
    
    // Debug logging start
    error_log("COMPARE BLOCKS: Starting comparison of block snapshots");
    error_log("NEW SNAPSHOTS TYPE: " . gettype($new_snapshots));
    error_log("OLD SNAPSHOTS TYPE: " . gettype($old_snapshots));
    error_log("NEW SNAPSHOTS COUNT: " . (is_array($new_snapshots) ? count($new_snapshots) : 'N/A'));
    error_log("OLD SNAPSHOTS COUNT: " . (is_array($old_snapshots) ? count($old_snapshots) : 'N/A'));

    // Log a full list of the block types in both snapshots
    $new_block_types = [];
    $old_block_types = [];
    
    if (is_array($new_snapshots)) {
        foreach ($new_snapshots as $idx => $block) {
            if (isset($block['blockName'])) {
                $new_block_types[] = $block['blockName'];
                
                // Special logging for regulatory and clarification blocks
                if (strpos($block['blockName'], 'regulatory') !== false || strpos($block['blockName'], 'clarification') !== false) {
                    error_log("FOUND BLOCK in NEW snapshots at index $idx: " . $block['blockName']);
                    if (isset($block['attrs'])) {
                        error_log("BLOCK ATTRS: " . json_encode($block['attrs']));
                    }
                    if (isset($block['innerHTML'])) {
                        error_log("BLOCK CONTENT: " . substr($block['innerHTML'], 0, 200) . "...");
                    }
                }
            }
        }
    }
    
    if (is_array($old_snapshots)) {
        foreach ($old_snapshots as $idx => $block) {
            if (isset($block['blockName'])) {
                $old_block_types[] = $block['blockName'];
                
                // Special logging for regulatory and clarification blocks
                if (strpos($block['blockName'], 'regulatory') !== false || strpos($block['blockName'], 'clarification') !== false) {
                    error_log("FOUND BLOCK in OLD snapshots at index $idx: " . $block['blockName']);
                    if (isset($block['attrs'])) {
                        error_log("BLOCK ATTRS: " . json_encode($block['attrs']));
                    }
                    if (isset($block['innerHTML'])) {
                        error_log("BLOCK CONTENT: " . substr($block['innerHTML'], 0, 200) . "...");
                    }
                }
            }
        }
    }
    
    error_log("NEW SNAPSHOT BLOCK TYPES: " . implode(", ", $new_block_types));
    error_log("OLD SNAPSHOT BLOCK TYPES: " . implode(", ", $old_block_types));
    
    // Handle edge cases - if either snapshot is invalid or empty
    if (!is_array($new_snapshots) || !is_array($old_snapshots)) {
        error_log("COMPARE BLOCKS WARNING: One or both snapshots are not arrays");
        if (!is_array($new_snapshots)) {
            error_log("NEW SNAPSHOTS INVALID: " . print_r($new_snapshots, true));
            $new_snapshots = array();
        }
        if (!is_array($old_snapshots)) {
            error_log("OLD SNAPSHOTS INVALID: " . print_r($old_snapshots, true));
            $old_snapshots = array();
        }
    }
    
    // Identify added and amended blocks
    foreach ($new_snapshots as $block_id => $block) {
        // Skip if block is not valid
        if (!is_array($block) || empty($block['blockName'])) {
            error_log("COMPARE BLOCKS: Skipping invalid block at index $block_id");
            continue;
        }
        
        $section_info = astp_get_block_section($block);
        
        // Track section order
        if (!empty($section_info['full_text']) && !in_array($section_info['full_text'], $sections_seen)) {
            $sections_seen[] = $section_info['full_text'];
            $changes['sections_order'][] = $section_info['full_text'];
        }
        
        // Check if this block exists in old snapshots
        $found_in_old = false;
        foreach ($old_snapshots as $old_block_id => $old_block) {
            if ($block['blockName'] === $old_block['blockName'] && 
                isset($block['attrs']['blockId']) && 
                isset($old_block['attrs']['blockId']) && 
                $block['attrs']['blockId'] === $old_block['attrs']['blockId']) {
                
                $found_in_old = true;
                
                // Compare block content
                $is_amended = false;
                
                // For regulatory and clarification blocks, compare attributes
                if (strpos($block['blockName'], 'regulatory') !== false || strpos($block['blockName'], 'clarification') !== false) {
                    if (isset($block['attrs']) && isset($old_block['attrs'])) {
                        // Log the comparison
                        error_log("COMPARING BLOCK ATTRIBUTES:");
                        error_log("NEW: " . json_encode($block['attrs']));
                        error_log("OLD: " . json_encode($old_block['attrs']));
                        
                        // Compare specific attributes that matter
                        $attrs_to_compare = ['sections', 'paragraphs', 'content'];
                        foreach ($attrs_to_compare as $attr) {
                            if (isset($block['attrs'][$attr]) && isset($old_block['attrs'][$attr])) {
                                if (json_encode($block['attrs'][$attr]) !== json_encode($old_block['attrs'][$attr])) {
                                    $is_amended = true;
                                    error_log("BLOCK AMENDED: Attribute '$attr' differs");
                                    break;
                                }
                            }
                        }
                    }
                } else {
                    // For other blocks, compare innerHTML
                    if (isset($block['innerHTML']) && isset($old_block['innerHTML'])) {
                        if ($block['innerHTML'] !== $old_block['innerHTML']) {
                            $is_amended = true;
                            error_log("BLOCK AMENDED: innerHTML differs");
                        }
                    }
                }
                
                if ($is_amended) {
                    $changes['amended'][] = array(
                        'section' => $section_info,
                        'old_block' => $old_block,
                        'new_block' => $block,
                        'raw_old_block' => $old_block,  // Store full block data
                        'raw_new_block' => $block       // Store full block data
                    );
                    error_log("ADDED AMENDED CHANGE: " . $block['blockName']);
                }
                
                break;
            }
        }
        
        if (!$found_in_old) {
            $changes['added'][] = array(
                'section' => $section_info,
                'block' => $block,
                'raw_block' => $block  // Store full block data
            );
            error_log("ADDED NEW CHANGE: " . $block['blockName']);
        }
    }
    
    // Identify removed blocks
    foreach ($old_snapshots as $old_block_id => $old_block) {
        if (!is_array($old_block) || empty($old_block['blockName'])) {
            continue;
        }
        
        $found_in_new = false;
        foreach ($new_snapshots as $block_id => $block) {
            if ($block['blockName'] === $old_block['blockName'] && 
                isset($block['attrs']['blockId']) && 
                isset($old_block['attrs']['blockId']) && 
                $block['attrs']['blockId'] === $old_block['attrs']['blockId']) {
                $found_in_new = true;
                break;
            }
        }
        
        if (!$found_in_new) {
            $section_info = astp_get_block_section($old_block);
            $changes['removed'][] = array(
                'section' => $section_info,
                'block' => $old_block,
                'raw_block' => $old_block  // Store full block data
            );
            error_log("ADDED REMOVED CHANGE: " . $old_block['blockName']);
        }
    }
    
    // Log final changes structure
    error_log("FINAL CHANGES STRUCTURE:");
    error_log("Added: " . count($changes['added']));
    error_log("Removed: " . count($changes['removed']));
    error_log("Amended: " . count($changes['amended']));
    error_log("Sections Order: " . implode(", ", $changes['sections_order']));
    
    return $changes;
}

/**
 * Extract section information from a block
 *
 * @param array $block The block data
 * @return array Section information (title, reference, etc.)
 */
function astp_get_block_section($block) {
    $section_info = array(
        'full_text' => '',
        'title' => '',
        'reference' => '',
        'blockName' => isset($block['blockName']) ? $block['blockName'] : ''
    );
    
    // If no block name, return empty section info
    if (empty($block['blockName'])) {
        return $section_info;
    }
    
    // Get block attributes and extract section info
    $attrs = isset($block['attrs']) && is_array($block['attrs']) ? $block['attrs'] : array();
    
    // Get a reference if it exists in attributes
    if (isset($attrs['reference'])) {
        $section_info['reference'] = $attrs['reference'];
    }
    
    // Get section title based on block type
    $block_type = str_replace('astp/', '', $block['blockName']);
    
    // Enhanced switch with more block types recognized
    switch ($block_type) {
        case 'update-deadlines':
            $section_info['title'] = 'Required Update Deadlines';
            break;
            
        case 'certification-dependencies':
            $section_info['title'] = 'Certification Dependencies';
            break;
            
        case 'standards-referenced':
            $section_info['title'] = 'Standards Referenced';
            break;
            
        case 'technical-explanations':
            $section_info['title'] = 'Technical Explanations';
            break;
            
        case 'title-section':
            if (!empty($attrs['documentTitle'])) {
                $section_info['title'] = $attrs['documentTitle'];
            } else {
                $section_info['title'] = 'Document Title';
            }
            break;
            
        case 'regulation-text':
            $section_info['title'] = 'Regulation Text';
            break;
            
        case 'revision-history':
            $section_info['title'] = 'Revision History';
            break;
            
        case 'core/paragraph':
            $section_info['title'] = 'Text Content';
            break;
            
        case 'core/heading':
            // Try to get the content of the heading if available
            if (!empty($block['innerHTML'])) {
                $title = wp_strip_all_tags($block['innerHTML']);
                if (!empty($title)) {
                    $section_info['title'] = 'Heading: ' . substr($title, 0, 30) . (strlen($title) > 30 ? '...' : '');
                } else {
                    $section_info['title'] = 'Heading';
                }
            } else {
                $section_info['title'] = 'Heading';
            }
            break;
            
        case 'core/table':
            $section_info['title'] = 'Table Content';
            break;
            
        case 'core/list':
            $section_info['title'] = 'List Content';
            break;
            
        case 'core/image':
            $section_info['title'] = 'Image';
            break;
            
        case 'core/file':
            $section_info['title'] = 'File Attachment';
            break;
            
        default:
            // Create a title from the block name
            $section_info['title'] = ucwords(str_replace('-', ' ', $block_type));
    }
    
    // Try to extract a reference from the content
    if (empty($section_info['reference']) && !empty($block['innerHTML'])) {
        // Look for paragraph references like (g)(10)(i)
        if (preg_match('/\(([a-z0-9]+)\)(?:\([a-z0-9]+\))*/i', $block['innerHTML'], $matches)) {
            $section_info['reference'] = $matches[0];
        }
    }
    
    // Use attribute content if available for update-deadlines
    if ($block_type === 'update-deadlines' && !empty($attrs['content'])) {
        // Attempt to enhance the title with the content
        $content_preview = substr($attrs['content'], 0, 30);
        if (strlen($content_preview) > 0) {
            $section_info['title'] = 'Required Update Deadlines: ' . $content_preview . (strlen($attrs['content']) > 30 ? '...' : '');
        }
    }
    
    // Create full text combining reference and title
    if (!empty($section_info['reference']) && !empty($section_info['title'])) {
        $section_info['full_text'] = "Paragraph {$section_info['reference']} - {$section_info['title']}";
    } elseif (!empty($section_info['reference'])) {
        $section_info['full_text'] = "Paragraph {$section_info['reference']}";
    } elseif (!empty($section_info['title'])) {
        $section_info['full_text'] = $section_info['title'];
    } else {
        $section_info['full_text'] = "Section " . ucwords(str_replace('-', ' ', $block_type));
    }
    
    // For debugging
    error_log("BLOCK SECTION: " . $section_info['full_text'] . " | Block: " . $block['blockName']);
    
    return $section_info;
}

/**
 * Helper function for word-level diff
 * 
 * @param string $old_text Old text
 * @param string $new_text New text
 * @return string HTML with highlighted differences
 */
function astp_word_diff($old_text, $new_text) {
    // Clean and normalize inputs
    $old_text = trim($old_text);
    $new_text = trim($new_text);
    
    // If texts are identical, return as is
    if ($old_text === $new_text) {
        return '<p>' . esc_html($new_text) . '</p>';
    }
    
    // If one is empty, show as completely changed
    if (empty($old_text)) {
        return '<p><ins>' . esc_html($new_text) . '</ins></p>';
    }
    
    if (empty($new_text)) {
        return '<p><del>' . esc_html($old_text) . '</del></p>';
    }
    
    // Split into words
    $old_words = preg_split('/(\s+)/', $old_text, -1, PREG_SPLIT_DELIM_CAPTURE);
    $new_words = preg_split('/(\s+)/', $new_text, -1, PREG_SPLIT_DELIM_CAPTURE);
    
    // Find the longest common subsequence
    $lcs = astp_find_longest_common_subsequence($old_words, $new_words);
    
    // Build the diff
    $diff_html = '<p>';
    $old_index = 0;
    $new_index = 0;
    
    foreach ($lcs as $position) {
        list($old_pos, $new_pos) = $position;
        
        // Add deleted content
        while ($old_index < $old_pos) {
            $diff_html .= '<del>' . esc_html($old_words[$old_index]) . '</del>';
            $old_index++;
        }
        
        // Add inserted content
        while ($new_index < $new_pos) {
            $diff_html .= '<ins>' . esc_html($new_words[$new_index]) . '</ins>';
            $new_index++;
        }
        
        // Add unchanged content
        $diff_html .= esc_html($new_words[$new_pos]);
        $old_index++;
        $new_index++;
    }
    
    // Add any remaining deleted content
    while ($old_index < count($old_words)) {
        $diff_html .= '<del>' . esc_html($old_words[$old_index]) . '</del>';
        $old_index++;
    }
    
    // Add any remaining inserted content
    while ($new_index < count($new_words)) {
        $diff_html .= '<ins>' . esc_html($new_words[$new_index]) . '</ins>';
        $new_index++;
    }
    
    $diff_html .= '</p>';
    return $diff_html;
}

/**
 * Find the longest common subsequence (helper for word diff)
 * 
 * @param array $a First array of words
 * @param array $b Second array of words
 * @return array Array of matched positions
 */
function astp_find_longest_common_subsequence($a, $b) {
    $matrix = array();
    $matches = array();
    
    // Build the matrix
    $matrix = array_fill(0, count($a) + 1, array_fill(0, count($b) + 1, 0));
    
    // Fill the matrix
    for ($i = 1; $i <= count($a); $i++) {
        for ($j = 1; $j <= count($b); $j++) {
            if ($a[$i-1] === $b[$j-1]) {
                $matrix[$i][$j] = $matrix[$i-1][$j-1] + 1;
            } else {
                $matrix[$i][$j] = max($matrix[$i-1][$j], $matrix[$i][$j-1]);
            }
        }
    }
    
    // Backtrack to find the matches
    $i = count($a);
    $j = count($b);
    
    while ($i > 0 && $j > 0) {
        if ($a[$i-1] === $b[$j-1]) {
            array_unshift($matches, array($i-1, $j-1));
            $i--;
            $j--;
        } elseif ($matrix[$i-1][$j] > $matrix[$i][$j-1]) {
            $i--;
        } else {
            $j--;
        }
    }
    
    return $matches;
}

/**
 * Generate a compact difference display between two text strings
 */
function astp_compact_diff($old_text, $new_text) {
    // Clean and normalize inputs
    $old_text = trim($old_text);
    $new_text = trim($new_text);
    
    // If texts are identical, return as is
    if ($old_text === $new_text) {
        return '<p>' . esc_html($new_text) . '</p>';
    }
    
    // If one is empty, show as completely changed
    if (empty($old_text)) {
        return '<div class="astp-side-by-side-diff">
            <div class="diff-old-content">Empty</div>
            <div class="diff-arrow">→</div>
            <div class="diff-new-content">' . esc_html($new_text) . '</div>
        </div>';
    }
    
    if (empty($new_text)) {
        return '<div class="astp-side-by-side-diff">
            <div class="diff-old-content">' . esc_html($old_text) . '</div>
            <div class="diff-arrow">→</div>
            <div class="diff-new-content">Empty</div>
        </div>';
    }
    
    // Try to detect character-level changes
    
    // Strip HTML tags for better comparison
    $old_plain = wp_strip_all_tags($old_text);
    $new_plain = wp_strip_all_tags($new_text);
    
    // Find the common prefix
    $i = 0;
    $max = min(strlen($old_plain), strlen($new_plain));
    
    while ($i < $max && $old_plain[$i] === $new_plain[$i]) {
        $i++;
    }
    
    $prefix = substr($old_plain, 0, $i);
    
    // Find the common suffix
    $old_suffix_start = strlen($old_plain);
    $new_suffix_start = strlen($new_plain);
    
    while ($old_suffix_start > $i && $new_suffix_start > $i && 
           $old_plain[$old_suffix_start - 1] === $new_plain[$new_suffix_start - 1]) {
        $old_suffix_start--;
        $new_suffix_start--;
    }
    
    $suffix = substr($new_plain, $new_suffix_start);
    
    // Extract the changed portions
    $old_middle = substr($old_plain, $i, $old_suffix_start - $i);
    $new_middle = substr($new_plain, $i, $new_suffix_start - $i);
    
    // Calculate if this is a very small change
    $is_tiny_change = strlen($old_middle) < 30 && strlen($new_middle) < 30 && 
                      strlen($prefix) > 0 && strlen($suffix) > 0;
    
    // For very small changes (like changing v6 to v7), use the tiny diff format
    if ($is_tiny_change) {
        return '<div class="astp-tiny-diff">
            <span class="diff-old-text">' . esc_html($old_middle) . '</span>
            <span class="diff-arrow">→</span>
            <span class="diff-new-text">' . esc_html($new_middle) . '</span>
        </div>';
    }
    
    // Check if we're dealing with similar size content that changed substantially
    $size_ratio = max(strlen($old_plain), strlen($new_plain)) / max(1, min(strlen($old_plain), strlen($new_plain)));
    $word_count_old = str_word_count($old_plain);
    $word_count_new = str_word_count($new_plain);
    $word_ratio = max($word_count_old, $word_count_new) / max(1, min($word_count_old, $word_count_new));
    
    // For moderate changes or content of similar size, use the side-by-side diff format
    if ($size_ratio < 1.5 && $word_ratio < 1.5) {
        return '<div class="astp-side-by-side-diff">
            <div class="diff-old-content">' . esc_html($old_plain) . '</div>
            <div class="diff-arrow">→</div>
            <div class="diff-new-content">' . esc_html($new_plain) . '</div>
        </div>';
    }
    
    // For content with common elements, build an inline diff
    if (strlen($prefix) > 10 || strlen($suffix) > 10) {
        $html = esc_html($prefix);
        
        if ($old_middle !== '') {
            $html .= '<del>' . esc_html($old_middle) . '</del>';
        }
        
        if ($new_middle !== '') {
            $html .= '<ins>' . esc_html($new_middle) . '</ins>';
        }
        
        $html .= esc_html($suffix);
        
        return '<div class="astp-inline-diff">' . $html . '</div>';
    }
    
    // If we reach here, the content is substantially different
    // Use a complete side-by-side diff
    return '<div class="astp-side-by-side-diff">
        <div class="diff-old-content">' . esc_html($old_plain) . '</div>
        <div class="diff-arrow">→</div>
        <div class="diff-new-content">' . esc_html($new_plain) . '</div>
    </div>';
}

/**
 * Generate a diff between two block versions
 *
 * @param array $old_block The old block data
 * @param array $new_block The new block data
 * @return array Diff information
 */
function astp_generate_block_diff($old_block, $new_block) {
    $diff = [];
    
    // Convert block content to text for comparison
    $old_content = '';
    $new_content = '';
    
    // Get the old block content
    if (isset($old_block['innerHTML']) && is_string($old_block['innerHTML'])) {
        $old_content = $old_block['innerHTML'];
    } elseif (isset($old_block['innerContent']) && is_array($old_block['innerContent'])) {
        $old_content = implode("\n", array_filter($old_block['innerContent']));
    }
    
    // Get the new block content
    if (isset($new_block['innerHTML']) && is_string($new_block['innerHTML'])) {
        $new_content = $new_block['innerHTML'];
    } elseif (isset($new_block['innerContent']) && is_array($new_block['innerContent'])) {
        $new_content = implode("\n", array_filter($new_block['innerContent']));
    }
    
    // For update-deadlines, always check content attribute if available
    if (isset($new_block['blockName']) && $new_block['blockName'] === 'astp/update-deadlines') {
        if (isset($old_block['attrs']['content'])) {
            $old_content = $old_block['attrs']['content'];
        }
        if (isset($new_block['attrs']['content'])) {
            $new_content = $new_block['attrs']['content'];
        }
    }
    
    // Clean up content for better diff
    $old_content = trim($old_content);
    $new_content = trim($new_content);
    
    // Get block attributes
    $old_attrs = isset($old_block['attrs']) && is_array($old_block['attrs']) ? $old_block['attrs'] : [];
    $new_attrs = isset($new_block['attrs']) && is_array($new_block['attrs']) ? $new_block['attrs'] : [];
    
    // For debugging
    error_log("DIFF for block: " . ($new_block['blockName'] ?? 'unknown'));
    error_log("Old content: " . substr($old_content, 0, 50) . (strlen($old_content) > 50 ? '...' : ''));
    error_log("New content: " . substr($new_content, 0, 50) . (strlen($new_content) > 50 ? '...' : ''));
    
    // Process updated table data if this is a table block
    $table_data_changed = false;
    $table_html = '';
    
    if (isset($new_block['blockName'])) {
        switch ($new_block['blockName']) {
            case 'astp/update-deadlines':
                // Compare deadlines array if it exists
                if (isset($new_attrs['deadlines']) && isset($old_attrs['deadlines'])) {
                    $old_deadlines = $old_attrs['deadlines'];
                    $new_deadlines = $new_attrs['deadlines'];
                    
                    if ($old_deadlines != $new_deadlines) {
                        $table_data_changed = true;
                        
                        // Generate HTML for the deadline changes
                        $table_html = '<div class="astp-table-changes">';
                        $table_html .= '<h4>Deadline Changes</h4>';
                        $table_html .= '<table class="wp-list-table widefat fixed striped">';
                        $table_html .= '<thead><tr><th>Requirement</th><th>Deadline</th><th>Description</th><th>Status</th></tr></thead>';
                        $table_html .= '<tbody>';
                        
                        // Map old deadlines for easy lookup
                        $old_map = [];
                        foreach ($old_deadlines as $idx => $deadline) {
                            $key = ($deadline['requirement'] ?? '') . '_' . ($deadline['date'] ?? '');
                            $old_map[$key] = $deadline;
                        }
                        
                        // Process new deadlines
                        foreach ($new_deadlines as $deadline) {
                            $req = isset($deadline['requirement']) ? $deadline['requirement'] : '';
                            $date = isset($deadline['date']) ? $deadline['date'] : '';
                            $desc = isset($deadline['description']) ? $deadline['description'] : '';
                            
                            $key = $req . '_' . $date;
                            
                            if (isset($old_map[$key])) {
                                // Check if description changed
                                $old_desc = isset($old_map[$key]['description']) ? $old_map[$key]['description'] : '';
                                
                                if ($desc != $old_desc) {
                                    $table_html .= '<tr>';
                                    $table_html .= '<td>' . esc_html($req) . '</td>';
                                    $table_html .= '<td>' . esc_html($date) . '</td>';
                                    $table_html .= '<td>';
                                    // Use compact diff for description changes
                                    $table_html .= astp_compact_diff($old_desc, $desc);
                                    $table_html .= '</td>';
                                    $table_html .= '<td>Updated</td>';
                                    $table_html .= '</tr>';
                                }
                                
                                // Remove from old map to track removed items
                                unset($old_map[$key]);
                            } else {
                                // New deadline
                                $table_html .= '<tr>';
                                $table_html .= '<td>' . esc_html($req) . '</td>';
                                $table_html .= '<td>' . esc_html($date) . '</td>';
                                $table_html .= '<td>' . esc_html($desc) . '</td>';
                                $table_html .= '<td>Added</td>';
                                $table_html .= '</tr>';
                            }
                        }
                        
                        // Process removed deadlines
                        foreach ($old_map as $deadline) {
                            $req = isset($deadline['requirement']) ? $deadline['requirement'] : '';
                            $date = isset($deadline['date']) ? $deadline['date'] : '';
                            $desc = isset($deadline['description']) ? $deadline['description'] : '';
                            
                            $table_html .= '<tr>';
                            $table_html .= '<td>' . esc_html($req) . '</td>';
                            $table_html .= '<td>' . esc_html($date) . '</td>';
                            $table_html .= '<td>' . esc_html($desc) . '</td>';
                            $table_html .= '<td>Removed</td>';
                            $table_html .= '</tr>';
                        }
                        
                        $table_html .= '</tbody>';
                        $table_html .= '</table>';
                        $table_html .= '</div>';
                    }
                }
                break;
                
            case 'astp/technical-explanations':
                // For technical explanations, always use the content attribute
                // This ensures we capture the content changes properly
                if (isset($old_attrs['content'])) {
                    $old_content = $old_attrs['content'];
                } else {
                    $old_content = '';
                }
                
                if (isset($new_attrs['content'])) {
                    $new_content = $new_attrs['content'];
                } else {
                    $new_content = '';
                }
                
                // Log for debugging
                error_log("Technical Explanations - Old: '" . $old_content . "', New: '" . $new_content . "'");
                break;

            // New USWDS blocks - CCG
            case 'uswds-gutenberg/clarification-block':
                // For clarification blocks, check sections array
                if (isset($old_attrs['sections']) && isset($new_attrs['sections'])) {
                    $old_sections = $old_attrs['sections'];
                    $new_sections = $new_attrs['sections'];
                    
                    if ($old_sections != $new_sections) {
                        $table_data_changed = true;
                        
                        // Generate HTML for the section changes
                        $table_html = '<div class="astp-table-changes">';
                        $table_html .= '<h4>Clarification Changes</h4>';
                        $table_html .= '<table class="wp-list-table widefat fixed striped">';
                        $table_html .= '<thead><tr><th>Paragraph</th><th>Heading</th><th>Content</th><th>Status</th></tr></thead>';
                        $table_html .= '<tbody>';
                        
                        // Map old sections for easy lookup by ID
                        $old_map = [];
                        foreach ($old_sections as $section) {
                            if (isset($section['id'])) {
                                $old_map[$section['id']] = $section;
                            }
                        }
                        
                        // Process new sections
                        foreach ($new_sections as $section) {
                            if (!isset($section['id'])) continue;
                            
                            $id = $section['id'];
                            $version = isset($section['version']) ? $section['version'] : '';
                            $heading = isset($section['heading']) ? $section['heading'] : '';
                            $content = isset($section['content']) ? $section['content'] : '';
                            
                            if (isset($old_map[$id])) {
                                // Compare to old section
                                $old_section = $old_map[$id];
                                $old_version = isset($old_section['version']) ? $old_section['version'] : '';
                                $old_heading = isset($old_section['heading']) ? $old_section['heading'] : '';
                                $old_content = isset($old_section['content']) ? $old_section['content'] : '';
                                
                                $changed = false;
                                
                                if ($version != $old_version || $heading != $old_heading || $content != $old_content) {
                                    $changed = true;
                                    
                                    $table_html .= '<tr>';
                                    $table_html .= '<td>' . esc_html($version) . '</td>';
                                    $table_html .= '<td>';
                                    if ($heading != $old_heading) {
                                        $table_html .= astp_compact_diff($old_heading, $heading);
                                    } else {
                                        $table_html .= esc_html($heading);
                                    }
                                    $table_html .= '</td>';
                                    $table_html .= '<td>';
                                    if ($content != $old_content) {
                                        $table_html .= astp_compact_diff($old_content, $content);
                                    } else {
                                        $table_html .= esc_html($content);
                                    }
                                    $table_html .= '</td>';
                                    $table_html .= '<td>Updated</td>';
                                    $table_html .= '</tr>';
                                }
                                
                                // Remove from old map to track removed items
                                unset($old_map[$id]);
                            } else {
                                // New section
                                $table_html .= '<tr>';
                                $table_html .= '<td>' . esc_html($version) . '</td>';
                                $table_html .= '<td>' . esc_html($heading) . '</td>';
                                $table_html .= '<td>' . esc_html($content) . '</td>';
                                $table_html .= '<td>Added</td>';
                                $table_html .= '</tr>';
                            }
                        }
                        
                        // Process removed sections
                        foreach ($old_map as $section) {
                            $version = isset($section['version']) ? $section['version'] : '';
                            $heading = isset($section['heading']) ? $section['heading'] : '';
                            $content = isset($section['content']) ? $section['content'] : '';
                            
                            $table_html .= '<tr>';
                            $table_html .= '<td>' . esc_html($version) . '</td>';
                            $table_html .= '<td>' . esc_html($heading) . '</td>';
                            $table_html .= '<td>' . esc_html($content) . '</td>';
                            $table_html .= '<td>Removed</td>';
                            $table_html .= '</tr>';
                        }
                        
                        $table_html .= '</tbody>';
                        $table_html .= '</table>';
                        $table_html .= '</div>';
                    }
                }
                break;
                
            case 'uswds-gutenberg/regulatory-block':
                // For regulatory blocks, check paragraphs array
                if (isset($old_attrs['paragraphs']) && isset($new_attrs['paragraphs'])) {
                    $old_paragraphs = $old_attrs['paragraphs'];
                    $new_paragraphs = $new_attrs['paragraphs'];
                    
                    if ($old_paragraphs != $new_paragraphs) {
                        $table_data_changed = true;
                        
                        // Generate HTML for the paragraph changes
                        $table_html = '<div class="astp-table-changes">';
                        $table_html .= '<h4>Regulatory Text Changes</h4>';
                        $table_html .= '<table class="wp-list-table widefat fixed striped">';
                        $table_html .= '<thead><tr><th>Paragraph</th><th>Content</th><th>Status</th></tr></thead>';
                        $table_html .= '<tbody>';
                        
                        // Map old paragraphs for easy lookup by ID
                        $old_map = [];
                        foreach ($old_paragraphs as $para) {
                            if (isset($para['id'])) {
                                $old_map[$para['id']] = $para;
                            }
                        }
                        
                        // Process new paragraphs
                        foreach ($new_paragraphs as $para) {
                            if (!isset($para['id'])) continue;
                            
                            $id = $para['id'];
                            $number = isset($para['number']) ? $para['number'] : '';
                            $content = isset($para['content']) ? $para['content'] : '';
                            
                            if (isset($old_map[$id])) {
                                // Compare to old paragraph
                                $old_para = $old_map[$id];
                                $old_number = isset($old_para['number']) ? $old_para['number'] : '';
                                $old_content = isset($old_para['content']) ? $old_para['content'] : '';
                                
                                if ($number != $old_number || $content != $old_content) {
                                    $table_html .= '<tr>';
                                    $table_html .= '<td>' . esc_html($number) . '</td>';
                                    $table_html .= '<td>';
                                    if ($content != $old_content) {
                                        $table_html .= astp_compact_diff($old_content, $content);
                                    } else {
                                        $table_html .= esc_html($content);
                                    }
                                    $table_html .= '</td>';
                                    $table_html .= '<td>Updated</td>';
                                    $table_html .= '</tr>';
                                }
                                
                                // Remove from old map to track removed items
                                unset($old_map[$id]);
                            } else {
                                // New paragraph
                                $table_html .= '<tr>';
                                $table_html .= '<td>' . esc_html($number) . '</td>';
                                $table_html .= '<td>' . esc_html($content) . '</td>';
                                $table_html .= '<td>Added</td>';
                                $table_html .= '</tr>';
                            }
                        }
                        
                        // Process removed paragraphs
                        foreach ($old_map as $para) {
                            $number = isset($para['number']) ? $para['number'] : '';
                            $content = isset($para['content']) ? $para['content'] : '';
                            
                            $table_html .= '<tr>';
                            $table_html .= '<td>' . esc_html($number) . '</td>';
                            $table_html .= '<td>' . esc_html($content) . '</td>';
                            $table_html .= '<td>Removed</td>';
                            $table_html .= '</tr>';
                        }
                        
                        $table_html .= '</tbody>';
                        $table_html .= '</table>';
                        $table_html .= '</div>';
                    }
                }
                break;
                
            case 'uswds-gutenberg/standards-block':
            case 'uswds-gutenberg/dependencies-block':
            case 'uswds-gutenberg/resources-block':
                // Generic handling for standards, dependencies, and resources blocks
                // Compare content directly
                if (isset($old_attrs['content']) && isset($new_attrs['content'])) {
                    $old_content = $old_attrs['content'];
                    $new_content = $new_attrs['content'];
                    
                    if ($old_content != $new_content) {
                        // Create a simple diff display
                        $block_type = str_replace('uswds-gutenberg/', '', $new_block['blockName']);
                        $block_type = str_replace('-block', '', $block_type);
                        $block_type = ucwords(str_replace('-', ' ', $block_type));
                        
                        $table_html = '<div class="astp-content-diff">';
                        $table_html .= '<h4>' . esc_html($block_type) . ' Content Changes</h4>';
                        $table_html .= astp_compact_diff($old_content, $new_content);
                        $table_html .= '</div>';
                        $table_data_changed = true;
                    }
                }
                break;
                
            case 'uswds-gutenberg/testing-components':
            case 'uswds-gutenberg/testing-steps-block': 
            case 'uswds-gutenberg/testing-tools-block':
                // Generic handling for testing related blocks
                // These will likely have different data structures based on their implementation
                // Add a generic note for now
                $block_type = str_replace('uswds-gutenberg/', '', $new_block['blockName']);
                $block_type = ucwords(str_replace('-', ' ', $block_type));
                
                $table_html = '<div class="astp-generic-diff">';
                $table_html .= '<p>Changes detected in ' . esc_html($block_type) . '</p>';
                
                // If content attribute exists, show a diff of that
                if (isset($old_attrs['content']) && isset($new_attrs['content'])) {
                    $old_content = $old_attrs['content'];
                    $new_content = $new_attrs['content'];
                    
                    if ($old_content != $new_content) {
                        $table_html .= astp_compact_diff($old_content, $new_content);
                        $table_data_changed = true;
                    }
                } 
                // If steps or items array exists, compare those
                else if ((isset($old_attrs['steps']) && isset($new_attrs['steps'])) || 
                         (isset($old_attrs['items']) && isset($new_attrs['items']))) {
                    $old_items = isset($old_attrs['steps']) ? $old_attrs['steps'] : 
                                (isset($old_attrs['items']) ? $old_attrs['items'] : []);
                    $new_items = isset($new_attrs['steps']) ? $new_attrs['steps'] : 
                                (isset($new_attrs['items']) ? $new_attrs['items'] : []);
                    
                    if ($old_items != $new_items) {
                        $table_html .= '<ul>';
                        
                        // Compare item counts
                        if (count($old_items) < count($new_items)) {
                            $table_html .= '<li>Items added: ' . (count($new_items) - count($old_items)) . '</li>';
                        } else if (count($old_items) > count($new_items)) {
                            $table_html .= '<li>Items removed: ' . (count($old_items) - count($new_items)) . '</li>';
                        }
                        
                        // Look for modified items by ID if available
                        $modified_count = 0;
                        if (isset($old_items[0]['id']) && isset($new_items[0]['id'])) {
                            $old_map = [];
                            foreach ($old_items as $item) {
                                if (isset($item['id'])) {
                                    $old_map[$item['id']] = $item;
                                }
                            }
                            
                            foreach ($new_items as $item) {
                                if (isset($item['id']) && isset($old_map[$item['id']])) {
                                    if ($item != $old_map[$item['id']]) {
                                        $modified_count++;
                                    }
                                }
                            }
                            
                            if ($modified_count > 0) {
                                $table_html .= '<li>Modified items: ' . $modified_count . '</li>';
                            }
                        }
                        
                        $table_html .= '</ul>';
                        $table_data_changed = true;
                    }
                }
                
                $table_html .= '</div>';
                break;
        }
    }
    
    // Check for attribute changes that provide more specific information
    // e.g., documentTitle in title-section block
    $attr_changes = [];
    if ($new_block['blockName'] === 'astp/title-section') {
        if (isset($new_attrs['documentTitle']) && isset($old_attrs['documentTitle']) && 
            $new_attrs['documentTitle'] !== $old_attrs['documentTitle']) {
            $attr_changes[] = [
                'name' => 'Document Title',
                'old' => $old_attrs['documentTitle'],
                'new' => $new_attrs['documentTitle']
            ];
        }
        
        if (isset($new_attrs['documentNumber']) && isset($old_attrs['documentNumber']) && 
            $new_attrs['documentNumber'] !== $old_attrs['documentNumber']) {
            $attr_changes[] = [
                'name' => 'Document Number',
                'old' => $old_attrs['documentNumber'],
                'new' => $new_attrs['documentNumber']
            ];
        }
        
        if (isset($new_attrs['documentDate']) && isset($old_attrs['documentDate']) && 
            $new_attrs['documentDate'] !== $old_attrs['documentDate']) {
            $attr_changes[] = [
                'name' => 'Document Date',
                'old' => $old_attrs['documentDate'],
                'new' => $new_attrs['documentDate']
            ];
        }
    }
    
    // For update-deadlines, check requirement changes
    if ($new_block['blockName'] === 'astp/update-deadlines') {
        // Check for content changes
        if (isset($old_attrs['content']) && isset($new_attrs['content']) && 
            $old_attrs['content'] !== $new_attrs['content']) {
            $attr_changes[] = [
                'name' => 'Content',
                'old' => $old_attrs['content'],
                'new' => $new_attrs['content'],
                'use_compact_diff' => true
            ];
        }
        
        // Check requirement values
        if (isset($old_attrs['deadlines']) && isset($new_attrs['deadlines'])) {
            $old_req = isset($old_attrs['deadlines'][0]['requirement']) ? $old_attrs['deadlines'][0]['requirement'] : '';
            $new_req = isset($new_attrs['deadlines'][0]['requirement']) ? $new_attrs['deadlines'][0]['requirement'] : '';
            
            if ($old_req !== $new_req) {
                $attr_changes[] = [
                    'name' => 'Requirement',
                    'old' => $old_req,
                    'new' => $new_req,
                    'use_compact_diff' => true
                ];
            }
        }
    }
    
    // Generate attribute changes HTML if needed
    $attr_html = '';
    if (!empty($attr_changes)) {
        $attr_html = '<div class="astp-attribute-changes">';
        $attr_html .= '<h4>Attribute Changes</h4>';
        $attr_html .= '<ul>';
        
        foreach ($attr_changes as $change) {
            $attr_html .= '<li><strong>' . esc_html($change['name']) . ':</strong> ';
            
            if (!empty($change['use_compact_diff'])) {
                $attr_html .= astp_compact_diff($change['old'], $change['new']);
            } else {
                $attr_html .= '<del>' . esc_html($change['old']) . '</del> → ';
                $attr_html .= '<ins>' . esc_html($change['new']) . '</ins>';
            }
            
            $attr_html .= '</li>';
        }
        
        $attr_html .= '</ul></div>';
    }
    
    // Check for changes in regular content
    if (empty($old_content) && empty($new_content)) {
        // If no HTML content but we have attribute changes or table data changes
        if (!empty($attr_html) || $table_data_changed) {
            $diff['html'] = $attr_html . $table_html;
        } else {
            error_log("Empty content in both blocks, creating placeholder diff");
            // Provide more meaningful description of the change
            $blockName = isset($new_block['blockName']) ? str_replace('astp/', '', $new_block['blockName']) : 'block';
            
            // Special handling for technical-explanations block
            if ($blockName === 'technical-explanations' && isset($new_attrs['content']) && isset($old_attrs['content'])) {
                // If content attribute exists, compare directly
                if ($new_attrs['content'] !== $old_attrs['content']) {
                    $diff['html'] = '<div class="astp-technical-explanations-diff">
                        <h4>Technical Explanations Content Changes</h4>
                        <div class="astp-side-by-side-diff">
                            <div class="diff-old-content">' . (empty($old_attrs['content']) ? '<em>Empty</em>' : nl2br(esc_html($old_attrs['content']))) . '</div>
                            <div class="diff-arrow">→</div>
                            <div class="diff-new-content">' . (empty($new_attrs['content']) ? '<em>Empty</em>' : nl2br(esc_html($new_attrs['content']))) . '</div>
                        </div>
                    </div>';
                    return $diff;
                }
            }
            
            $diff['html'] = '<div class="structure-changed-diff">
                <p>The ' . esc_html($blockName) . ' structure was updated, but no visible text content was changed.</p>
                <p>This typically occurs when formatting or layout was modified.</p>
            </div>';
        }
    } else if ($old_content !== $new_content) {
        // Create plain text versions for simpler diffs
        $old_text = wp_strip_all_tags($old_content);
        $new_text = wp_strip_all_tags($new_content);
        
        // Log for debugging
        error_log("Old content: " . substr($old_text, 0, 100) . (strlen($old_text) > 100 ? '...' : ''));
        error_log("New content: " . substr($new_text, 0, 100) . (strlen($new_text) > 100 ? '...' : ''));
        
        // For completely empty old or new text, show a simple replacement diff
        if (empty($old_text) || empty($new_text)) {
            $diff['html'] = '<div class="astp-side-by-side-diff">
                <div class="diff-old-content">' . (empty($old_text) ? 'Empty' : esc_html($old_text)) . '</div>
                <div class="diff-arrow">→</div>
                <div class="diff-new-content">' . (empty($new_text) ? 'Empty' : esc_html($new_text)) . '</div>
            </div>';
        }
        // For very short text changes, use our compact diff
        else if (strlen($old_text) < 1000 && strlen($new_text) < 1000) {
            $diff['html'] = astp_compact_diff($old_text, $new_text);
        }
        // For longer content, try wp_text_diff if available
        else if (function_exists('wp_text_diff')) {
            $text_diff = wp_text_diff(
                $old_content, 
                $new_content,
                [
                    'show_split_view' => true,
                    'title_old' => 'Previous Version',
                    'title_new' => 'Current Version'
                ]
            );
            
            if (!empty($text_diff)) {
                // Enhance the diff with styling
                $text_diff = str_replace('table class="diff"', 'table class="diff astp-content-diff"', $text_diff);
    $diff['html'] = $text_diff;
            }
            // If wp_text_diff failed, fall back to our own diff
            else {
                $diff['html'] = astp_compact_diff($old_text, $new_text);
            }
        }
        // Fall back to our own diff
        else {
            $diff['html'] = astp_compact_diff($old_text, $new_text);
        }
        
        // If we still couldn't generate a diff, create a simple comparison
        if (empty($diff['html'])) {
            $diff['html'] = '<div class="astp-manual-diff">
                <div class="diff-old"><h4>Previous:</h4>' . esc_html($old_text) . '</div>
                <div class="diff-new"><h4>Current:</h4>' . esc_html($new_text) . '</div>
            </div>';
        }
        
        // Add attribute changes if any
        if (!empty($attr_html)) {
            $diff['html'] = $attr_html . $diff['html'];
        }
        
        // Add table data changes if any
        if ($table_data_changed) {
            $diff['html'] .= $table_html;
        }
    } else {
        // Content is the same, but we might have attribute or table changes
        if (!empty($attr_html) || $table_data_changed) {
            $diff['html'] = $attr_html . $table_html;
        } else {
            // No visible changes but structure might have changed
            $diff['html'] = '<div class="no-visible-changes">No visible content changes</div>';
        }
    }
    
    // Store original content for reference
    $diff['old_content'] = $old_content;
    $diff['new_content'] = $new_content;
    
    // Extract section and title info from the new block
    $section_info = astp_get_block_section($new_block);
    $diff['reference'] = $section_info['reference'];
    $diff['title'] = $section_info['title'];
    $diff['section'] = $section_info['full_text'];
    
    return $diff;
}

/**
 * Calculate new version number based on version type
 *
 * @param int    $current_version_id The ID of the current version post
 * @param string $version_type       The type of version ('major', 'minor', or 'hotfix')
 * @return string The new version number
 */
function astp_calculate_new_version_number($current_version_id, $version_type) {
    if (!$current_version_id) {
        return '1.0'; // Initial version
    }
    
    $current_number = get_post_meta($current_version_id, 'version_number', true);
    
    if (!$current_number) {
        return '1.0'; // Fallback if no version number found
    }
    
    $parts = explode('.', $current_number);
    
    if ($version_type === 'major') {
        $major = (int)$parts[0] + 1;
        return $major . '.0';
    } elseif ($version_type === 'minor') {
        $major = (int)$parts[0];
        $minor = isset($parts[1]) ? (int)$parts[1] + 1 : 1;
        return $major . '.' . $minor;
    } elseif ($version_type === 'hotfix') {
        // Hotfixes should be handled by astp_create_hotfix_version()
        // This is just a fallback
        $major = (int)$parts[0];
        $minor = isset($parts[1]) ? (int)$parts[1] : 0;
        $patch = isset($parts[2]) ? (int)$parts[2] + 1 : 1;
        return $major . '.' . $minor . '.' . $patch;
    }
    
    // Fallback
    return $current_number . '-new';
}

/**
 * Publishes a version and updates parent post references
 *
 * @param int    $post_id       The parent test method post ID
 * @param int    $version_id    Optional specific version ID to publish 
 * @param string $document_type Optional document type (ccg or tp)
 * @return bool  Success or failure
 */
function astp_publish_version($post_id, $version_id = null, $document_type = null) {
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log("PUBLISH VERSION: Starting publish process for post $post_id, version $version_id, type $document_type");
    }
    
    // If no version ID provided, get the in-development version
    if (!$version_id) {
        if ($document_type) {
            $version_id = get_post_meta($post_id, "{$document_type}_in_development_version", true);
        } else {
        $version_id = get_post_meta($post_id, 'in_development_version', true);
        }
    }
    
    // Check if we have a version to publish
    if (!$version_id) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("PUBLISH VERSION: No version ID found to publish");
        }
        return false;
    }
    
    // Get the version post
    $version = get_post($version_id);
    if (!$version || 'astp_version' !== $version->post_type) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("PUBLISH VERSION: Invalid version post");
        }
        return false;
    }
    
    // Check if the version has a document type
    if (!$document_type) {
        $document_type = get_post_meta($version_id, 'document_type', true);
    }
    
    // Set version status to published
    wp_set_object_terms($version_id, 'published', 'version_status');
    
    // Update version metadata
    update_post_meta($version_id, 'published_date', date('Y-m-d'));
    
    // Get the previous version
    $previous_version_id = get_post_meta($version_id, 'previous_version_id', true);
    
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log("PUBLISH VERSION: Previous version ID: $previous_version_id");
    }
    
    // Store block snapshots before generating changelog
    $block_snapshots = get_post_meta($version_id, 'block_snapshots', true);
    if (!$block_snapshots) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("PUBLISH VERSION: No block snapshots found, storing them now");
        }
        astp_store_block_snapshots($post_id, $version_id);
    }
    
    // If this is a new version (not a hotfix for another version)
    if (!get_post_meta($version_id, 'is_hotfix_for', true)) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("PUBLISH VERSION: Processing regular version (not hotfix)");
        }
        
        // Generate the changelog
        $changelog_id = astp_create_changelog($version_id, $previous_version_id);
        
        if ($changelog_id) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("PUBLISH VERSION: Successfully created changelog $changelog_id");
            }
            update_post_meta($version_id, 'changelog_id', $changelog_id);
                } else {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("PUBLISH VERSION: Failed to create changelog");
            }
        }
        
        // Update parent post references
        if ($document_type) {
            // Update document-specific current version
            update_post_meta($post_id, "{$document_type}_current_published_version", $version_id);
            delete_post_meta($post_id, "{$document_type}_in_development_version");
                    } else {
            // Legacy approach
            update_post_meta($post_id, 'current_published_version', $version_id);
            delete_post_meta($post_id, 'in_development_version');
        }
        
        // If the previous version was a hotfix base, mark it as deprecated
        if ($previous_version_id) {
            $hotfixes = get_post_meta($previous_version_id, 'hotfix_versions', true);
            if ($hotfixes && is_array($hotfixes) && !empty($hotfixes)) {
                wp_set_object_terms($previous_version_id, 'deprecated', 'version_status');
            }
        }
            } else {
        // This is a hotfix, handle differently
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("PUBLISH VERSION: Processing hotfix version");
        }
        
        $hotfix_base_id = get_post_meta($version_id, 'is_hotfix_for', true);
        
        if ($hotfix_base_id) {
            // Mark the base version as deprecated
            wp_set_object_terms($hotfix_base_id, 'deprecated', 'version_status');
            
            // Update parent references to point to this hotfix
            if ($document_type) {
                update_post_meta($post_id, "{$document_type}_current_published_version", $version_id);
            } else {
                update_post_meta($post_id, 'current_published_version', $version_id);
            }
            
            // Remove hotfix in development reference
            delete_post_meta($post_id, 'hotfix_in_development');
            if ($document_type) {
                delete_post_meta($post_id, "{$document_type}_hotfix_in_development");
            }
            
            // Generate changelog for hotfix
            $changelog_id = astp_create_changelog($version_id, $hotfix_base_id);
            if ($changelog_id) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log("PUBLISH VERSION: Successfully created hotfix changelog $changelog_id");
                }
                update_post_meta($version_id, 'changelog_id', $changelog_id);
            }
        }
    }
    
    // Log the version publication
    astp_log_version_event($version_id, 'published');
    
    // Fire action hooks
    do_action('astp_version_published', $version_id, $post_id);
    do_action('astp_version_status_changed', $version_id, 'published');
    
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log("PUBLISH VERSION: Successfully completed publish process");
    }
    
    return true;
}

/**
 * Prepare and create changelog for a version
 *
 * @param int $version_id The version ID to create changelog for
 * @param int $previous_version_id The previous version ID to compare against
 * @return int|bool Changelog ID on success, false on failure
 */
function astp_prepare_version_changelog($version_id, $previous_version_id = null) {
    if (!$version_id) {
        error_log("CHANGELOG ERROR: Invalid version ID provided to prepare_version_changelog");
        return false;
    }
    
    if (!$previous_version_id) {
        // Try to get the previous version ID from metadata
        $previous_version_id = get_post_meta($version_id, 'previous_version_id', true);
        
        if (!$previous_version_id) {
            error_log("CHANGELOG ERROR: No previous version ID available for comparison");
            return false;
        }
    }
    
    // Check if there's already a changelog for this version
    $existing_changelog = astp_get_changelog_for_version($version_id);
    
    if ($existing_changelog) {
        // Delete existing changelog if it exists
        error_log("CHANGELOG INFO: Deleting existing changelog {$existing_changelog->ID} for version {$version_id}");
        wp_delete_post($existing_changelog->ID, true);
    }
    
    // Create new changelog
    return astp_create_changelog($version_id, $previous_version_id);
}

/**
 * Create a changelog
 */
function astp_create_changelog($version_id, $old_version_id = null) {
    error_log("CHANGELOG START: Creating changelog for version $version_id compared to $old_version_id");
    
    // Validate parameters
    if (empty($version_id)) {
        error_log("CHANGELOG ERROR: Empty version_id provided");
        return false;
    }
    
    // Skip if no old version to compare against
    if (!$old_version_id) {
        // Try to get the previous version ID from metadata
        $old_version_id = get_post_meta($version_id, 'previous_version_id', true);
        
        if (!$old_version_id) {
            error_log("CHANGELOG ERROR: No previous version ID found for comparison");
            return false;
        }
    }
    
    // Get version info
    $version = get_post($version_id);
    $old_version = get_post($old_version_id);
    
    if (!$version || !$old_version) {
        error_log("CHANGELOG ERROR: Could not retrieve version posts. Current: " . ($version ? 'found' : 'not found') . 
                 ", Previous: " . ($old_version ? 'found' : 'not found'));
        return false;
    }
    
    // Get content snapshots
        $content_snapshot = get_post_meta($version_id, 'content_snapshot', true);
    $old_content_snapshot = get_post_meta($old_version_id, 'content_snapshot', true);
    
    // Make sure we have content snapshots
    if (empty($content_snapshot)) {
        error_log("CHANGELOG WARNING: Empty content snapshot for version $version_id");
            $content_snapshot = '';
    }
    
    if (empty($old_content_snapshot)) {
        error_log("CHANGELOG WARNING: Empty content snapshot for old version $old_version_id");
        $old_content_snapshot = '';
    }
    
    // Add more detailed logging
    error_log("CHANGELOG INFO: Creating changelog for version {$version_id}, comparing with {$old_version_id}");
    
    // Get block snapshots
    $block_snapshots = get_post_meta($version_id, 'block_snapshots', true);
    $old_block_snapshots = get_post_meta($old_version_id, 'block_snapshots', true);
    
    // Log snapshot data
    error_log("BLOCK SNAPSHOTS: " . (is_array($block_snapshots) ? count($block_snapshots) . " blocks found" : "No blocks found or invalid format: " . gettype($block_snapshots)));
    error_log("OLD BLOCK SNAPSHOTS: " . (is_array($old_block_snapshots) ? count($old_block_snapshots) . " blocks found" : "No blocks found or invalid format: " . gettype($old_block_snapshots)));
    
    try {
        // Create new changelog post
        $changelog_args = array(
            'post_title'    => 'Changelog for ' . get_the_title($version_id),
            'post_status'   => 'publish',
            'post_type'     => 'astp_changelog',
            'post_parent'   => $version_id,
            'post_content'  => ''
        );
        
        $changelog_id = wp_insert_post($changelog_args);
        
        if (!$changelog_id || is_wp_error($changelog_id)) {
            error_log("CHANGELOG CREATION FAILED: " . ($changelog_id && is_wp_error($changelog_id) ? $changelog_id->get_error_message() : "Unknown error"));
            return false;
        }
        
        // Store relationship meta
        update_post_meta($changelog_id, 'version_id', $version_id);
        update_post_meta($changelog_id, 'previous_version_id', $old_version_id);
        
        // Ensure block snapshots are arrays
        if (!is_array($block_snapshots)) {
            error_log("CHANGELOG WARNING: Converting block_snapshots to empty array");
            $block_snapshots = array();
        }
        
        if (!is_array($old_block_snapshots)) {
            error_log("CHANGELOG WARNING: Converting old_block_snapshots to empty array");
            $old_block_snapshots = array();
        }
        
        // Compare blocks to identify changes
        error_log("CHANGELOG INFO: Comparing block snapshots");
        $changes = astp_compare_block_snapshots($block_snapshots, $old_block_snapshots);
        
        // Log the changes data for debugging
        error_log('CHANGELOG SUMMARY: Added: ' . count($changes['added']) . ', Removed: ' . count($changes['removed']) . ', Amended: ' . count($changes['amended']));
        
        // Store changes data
        update_post_meta($changelog_id, 'changes_data', $changes);
        
        // Generate content for the changelog
        error_log("CHANGELOG INFO: Generating changelog content");
        $content = astp_generate_changelog_content($changes);
        
        // Update the changelog post with the generated content
        $update_result = wp_update_post(array(
            'ID'           => $changelog_id,
            'post_content' => $content,
            'post_excerpt' => astp_generate_changelog_summary($changes)
        ));
        
        if (!$update_result || is_wp_error($update_result)) {
            error_log("CHANGELOG ERROR: Failed to update post with content: " . 
                     (is_wp_error($update_result) ? $update_result->get_error_message() : "Unknown error"));
        }
        
        error_log("CHANGELOG SUCCESS: Completed creating changelog ID $changelog_id");
        return $changelog_id;
            } catch (Exception $e) {
        error_log("CHANGELOG EXCEPTION: " . $e->getMessage() . "\n" . $e->getTraceAsString());
        return false;
            } catch (Error $e) {
        error_log("CHANGELOG PHP ERROR: " . $e->getMessage() . "\n" . $e->getTraceAsString());
    return false;
    }
}

/**
 * Generate HTML content for the changelog
 *
 * @param array $changes Array of changes by type
 * @return string HTML content
 */
function astp_generate_changelog_content($changes) {
    if (empty($changes)) {
        return '<div class="astp-no-changes"><p>No changes detected in this version.</p></div>';
    }
    
    // Ensure we have all expected change types
    $changes = array_merge([
        'added' => [],
        'removed' => [],
        'amended' => [],
        'sections_order' => []
    ], $changes);
    
    // Helper function to ensure content is a string
    $ensure_string = function($content) {
        if (is_array($content)) {
            // If it's an array, convert it to JSON
            return json_encode($content);
        }
        return (string)$content;
    };
    
    $content = '';
    $content .= '<div class="astp-changelog">';
    
    // Summary
    $content .= '<div class="astp-changes-summary">';
    $content .= '<div class="astp-changes-counts">';
    
    if (count($changes['added']) > 0) {
        $content .= '<span class="astp-changes-badge astp-badge-added">' . count($changes['added']) . ' Addition' . (count($changes['added']) != 1 ? 's' : '') . '</span>';
    }
    
    if (count($changes['removed']) > 0) {
        $content .= '<span class="astp-changes-badge astp-badge-removed">' . count($changes['removed']) . ' Removal' . (count($changes['removed']) != 1 ? 's' : '') . '</span>';
    }
    
    if (count($changes['amended']) > 0) {
        $content .= '<span class="astp-changes-badge astp-badge-amended">' . count($changes['amended']) . ' Amendment' . (count($changes['amended']) != 1 ? 's' : '') . '</span>';
    }
    
    $content .= '</div>'; // .astp-changes-counts
    $content .= '</div>'; // .astp-changes-summary
    
    // Changes by section
    $content .= '<div class="astp-changes-content">';
    
    // Organize changes by section for better readability
    $sections = array();
    
    // Process added changes by section
    foreach ($changes['added'] as $change) {
        $section_key = 'General';
        
        if (!empty($change['section'])) {
            if (is_array($change['section']) && !empty($change['section']['full_text'])) {
                $section_key = $change['section']['full_text'];
            } elseif (is_string($change['section'])) {
                $section_key = $change['section'];
            }
        }
        
        if (!isset($sections[$section_key])) {
            $section_info = is_array($change['section']) ? $change['section'] : ['full_text' => $section_key];
            $sections[$section_key] = [
                'info' => $section_info,
                'added' => [],
                'removed' => [],
                'amended' => []
            ];
        }
        
        $sections[$section_key]['added'][] = $change;
    }
    
    // Process removed changes by section
    foreach ($changes['removed'] as $change) {
        $section_key = 'General';
        
        if (!empty($change['section'])) {
            if (is_array($change['section']) && !empty($change['section']['full_text'])) {
                $section_key = $change['section']['full_text'];
            } elseif (is_string($change['section'])) {
                $section_key = $change['section'];
            }
        }
        
        if (!isset($sections[$section_key])) {
            $section_info = is_array($change['section']) ? $change['section'] : ['full_text' => $section_key];
            $sections[$section_key] = [
                'info' => $section_info,
                'added' => [],
                'removed' => [],
                'amended' => []
            ];
        }
        
        $sections[$section_key]['removed'][] = $change;
    }
    
    // Process amended changes by section
    foreach ($changes['amended'] as $change) {
        $section_key = 'General';
        
        if (!empty($change['section'])) {
            if (is_array($change['section']) && !empty($change['section']['full_text'])) {
                $section_key = $change['section']['full_text'];
            } elseif (is_string($change['section'])) {
                $section_key = $change['section'];
            }
        }
        
        if (!isset($sections[$section_key])) {
            $section_info = is_array($change['section']) ? $change['section'] : ['full_text' => $section_key];
            $sections[$section_key] = [
                'info' => $section_info,
                'added' => [],
                'removed' => [],
                'amended' => []
            ];
        }
        
        $sections[$section_key]['amended'][] = $change;
    }
    
    // Order sections based on sections_order if available
    $ordered_sections = array();
    
    if (!empty($changes['sections_order'])) {
        foreach ($changes['sections_order'] as $section_text) {
            if (isset($sections[$section_text])) {
                $ordered_sections[$section_text] = $sections[$section_text];
                unset($sections[$section_text]);
            }
        }
    }
    
    // Add remaining sections
    foreach ($sections as $key => $section) {
        $ordered_sections[$key] = $section;
    }
    
    // Output sections
    foreach ($ordered_sections as $section_key => $section) {
        // Only include sections with changes
        if (empty($section['added']) && empty($section['removed']) && empty($section['amended'])) {
            continue;
        }
        
        $content .= '<div class="astp-revision-section">';
        
        // Section header
        $content .= '<h3 class="astp-section-heading">';
        
        if (!empty($section['info']['title'])) {
            $content .= '<span class="astp-section-title">' . esc_html($section['info']['title']) . '</span>';
        } elseif ($section_key !== 'General') {
            $content .= esc_html($section_key);
                } else {
            $content .= 'General Changes';
        }
        
        $content .= '</h3>';
        
        // Added changes
        if (!empty($section['added'])) {
            $content .= '<div class="astp-change-group astp-change-group-added">';
            $content .= '<h4 class="astp-change-type-heading">Added Content</h4>';
            $content .= '<ul class="astp-change-list">';
            
            foreach ($section['added'] as $change) {
                $content .= '<li class="astp-change-item">';
                
                // Add a header if we have reference info
                if (!empty($change['section']) && !empty($change['section']['reference'])) {
                    $content .= '<div class="astp-change-header">';
                    $content .= '<span class="astp-change-reference">' . esc_html($change['section']['reference']) . '</span>';
                    $content .= '</div>';
                }
                
                if (!empty($change['block'])) {
                    // Try to get content from block innerHtml
                    if (!empty($change['block']['innerHTML'])) {
                        $block_content = $ensure_string($change['block']['innerHTML']);
                        $content .= '<div class="astp-change-content">' . $block_content . '</div>';
                    } elseif (!empty($change['content'])) {
                        $content .= '<div class="astp-change-content">' . $ensure_string($change['content']) . '</div>';
                    } elseif (!empty($change['block']['attrs']['content'])) {
                        // Try to get content from attributes if available
                        $content .= '<div class="astp-change-content">' . esc_html($change['block']['attrs']['content']) . '</div>';
                    } else {
                        $content .= '<div class="astp-change-content"><p>• New content added</p></div>';
                    }
                } else {
                    $content .= '<div class="astp-change-content"><p>• New content added</p></div>';
                }
                
                $content .= '</li>';
            }
            
            $content .= '</ul>';
            $content .= '</div>'; // .astp-change-group
        }
        
        // Removed changes
        if (!empty($section['removed'])) {
            $content .= '<div class="astp-change-group astp-change-group-removed">';
            $content .= '<h4 class="astp-change-type-heading">Removed Content</h4>';
            $content .= '<ul class="astp-change-list">';
            
            foreach ($section['removed'] as $change) {
                $content .= '<li class="astp-change-item">';
                
                // Add a header if we have reference info
                if (!empty($change['section']) && !empty($change['section']['reference'])) {
                    $content .= '<div class="astp-change-header">';
                    $content .= '<span class="astp-change-reference">' . esc_html($change['section']['reference']) . '</span>';
                    $content .= '</div>';
                }
                
                if (!empty($change['block'])) {
                    // Try to get content from block innerHtml
                    if (!empty($change['block']['innerHTML'])) {
                        $block_content = $ensure_string($change['block']['innerHTML']);
                        $content .= '<div class="astp-change-content">' . $block_content . '</div>';
                    } elseif (!empty($change['content'])) {
                        $content .= '<div class="astp-change-content">' . $ensure_string($change['content']) . '</div>';
                    } elseif (!empty($change['block']['attrs']['content'])) {
                        // Try to get content from attributes if available
                        $content .= '<div class="astp-change-content">' . esc_html($change['block']['attrs']['content']) . '</div>';
                    } else {
                        $content .= '<div class="astp-change-content"><p>• Content removed</p></div>';
                    }
                } else {
                    $content .= '<div class="astp-change-content"><p>• Content removed</p></div>';
                }
                
                $content .= '</li>';
            }
            
            $content .= '</ul>';
            $content .= '</div>'; // .astp-change-group
        }
        
        // Amended changes
        if (!empty($section['amended'])) {
            $content .= '<div class="astp-change-group astp-change-group-amended">';
            $content .= '<h4 class="astp-change-type-heading">Amended Content</h4>';
            $content .= '<ul class="astp-change-list">';
            
            foreach ($section['amended'] as $change) {
                $content .= '<li class="astp-change-item">';
                
                // Add a header if we have reference info
                if (!empty($change['section']) && !empty($change['section']['reference'])) {
                    $content .= '<div class="astp-change-header">';
                    $content .= '<span class="astp-change-reference">' . esc_html($change['section']['reference']) . '</span>';
                    $content .= '</div>';
                }
                
                // Check if we have a generated diff in the change data
                if (!empty($change['diff']) && !empty($change['diff']['html'])) {
                    $content .= '<div class="astp-change-diff">' . $change['diff']['html'] . '</div>';
                } elseif (!empty($change['diff'])) {
                    $content .= '<div class="astp-change-diff">' . $ensure_string($change['diff']) . '</div>';
                } elseif (!empty($change['old_content']) && !empty($change['new_content'])) {
                    // Manual diff using old and new content
                    $content .= '<div class="astp-manual-diff">';
                    $content .= '<div class="diff-old"><h4>Previous:</h4>' . $ensure_string($change['old_content']) . '</div>';
                    $content .= '<div class="diff-new"><h4>Current:</h4>' . $ensure_string($change['new_content']) . '</div>';
                    $content .= '</div>';
                } elseif (!empty($change['old_block']) && !empty($change['new_block'])) {
                    // If we have blocks but no diff, try to generate one
                    if (function_exists('astp_generate_block_diff')) {
                        $diff = astp_generate_block_diff($change['old_block'], $change['new_block']);
                        if (!empty($diff['html'])) {
                            $content .= '<div class="astp-change-diff">' . $diff['html'] . '</div>';
                        } else {
                            $content .= '<div class="astp-change-content"><p>• Content was updated</p></div>';
                        }
                    } else {
                        $content .= '<div class="astp-change-content"><p>• Content was updated</p></div>';
                    }
                } elseif (!empty($change['new_block']) && !empty($change['new_block']['innerHTML'])) {
                    // Fallback to just showing the new content
                    $content .= '<div class="astp-change-content">' . $ensure_string($change['new_block']['innerHTML']) . '</div>';
                } elseif (!empty($change['new_block']) && !empty($change['new_block']['attrs']['content'])) {
                    // Try to get content from attributes if available
                    $content .= '<div class="astp-change-content">' . esc_html($change['new_block']['attrs']['content']) . '</div>';
                } else {
                    $content .= '<div class="astp-change-content"><p>• Content was updated</p></div>';
                }
                
                $content .= '</li>';
            }
            
            $content .= '</ul>';
            $content .= '</div>'; // .astp-change-group
        }
        
        $content .= '</div>'; // .astp-revision-section
    }
    
    $content .= '</div>'; // .astp-changes-content
    $content .= '</div>'; // .astp-changelog
    
    return $content;
}

/**
 * Generate a summary of the changelog for use in excerpts and metadata
 *
 * @param array $changes Array of changes by type
 * @return string Short summary text
 */
function astp_generate_changelog_summary($changes) {
    $added = count($changes['added']);
    $removed = count($changes['removed']);
    $amended = count($changes['amended']);
    
    $parts = array();
    
    if ($added > 0) {
        $parts[] = $added . ' addition' . ($added > 1 ? 's' : '');
    }
    
    if ($removed > 0) {
        $parts[] = $removed . ' removal' . ($removed > 1 ? 's' : '');
    }
    
    if ($amended > 0) {
        $parts[] = $amended . ' amendment' . ($amended > 1 ? 's' : '');
    }
    
    if (empty($parts)) {
        return 'No changes detected';
    }
    
    return 'This version includes ' . implode(', ', $parts) . '.';
}

/**
 * Store changelog data in post meta
 *
 * @param int   $changelog_id The changelog post ID
 * @param array $changes      Array of changes
 * @return bool True on success, false on failure
 */
function astp_store_changelog_data($changelog_id, $changes) {
    if (empty($changelog_id) || !is_numeric($changelog_id)) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("Invalid changelog ID: " . print_r($changelog_id, true));
        }
        return false;
    }
    
    if (empty($changes) || !is_array($changes)) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("Invalid changes array: " . print_r($changes, true));
        }
        return false;
    }
    
    // Log the raw changes for debugging
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log("RAW CHANGES for changelog ID $changelog_id: " . print_r($changes, true));
    }
    
    // Initialize the data structure
    $organized_changes = array();
    $sections_order = array();
    
    // Make sure the changes array has the expected structure
    if (!isset($changes['added'])) $changes['added'] = array();
    if (!isset($changes['removed'])) $changes['removed'] = array();
    if (!isset($changes['amended'])) $changes['amended'] = array();
    if (!isset($changes['sections_order'])) $changes['sections_order'] = array();
    
    // Log counts for debugging
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log("Processing " . count($changes['added']) . " added changes");
        error_log("Processing " . count($changes['removed']) . " removed changes");
        error_log("Processing " . count($changes['amended']) . " amended changes");
    }
    
    // Helper function to determine section key based on the change data
    $get_section_key = function($change) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("Determining section key for change: " . print_r($change, true));
        }
        
        // Try to find a section identifier
        $section = '';
        
        // Try to get from explicit section attribute
        if (!empty($change['section'])) {
            $section = $change['section'];
        }
        // Try to get from paragraph or block data
        else if (!empty($change['paragraph'])) {
            $section = $change['paragraph'];
        }
        else if (!empty($change['block']['blockName'])) {
            $block_name = $change['block']['blockName'];
            $section = "Block: $block_name";
            
            // Special handling for regulatory blocks
            if (strpos($block_name, 'regulatory') !== false) {
                if (!empty($change['block']['attrs']['paragraphs'][0]['number'])) {
                    $regulation_number = $change['block']['attrs']['paragraphs'][0]['number'];
                    $section = "Paragraph $regulation_number - $block_name";
                }
            }
        }
        else if (!empty($change['new_block']['blockName'])) {
            $block_name = $change['new_block']['blockName'];
            $section = "Block: $block_name";
            
            // Special handling for regulatory blocks
            if (strpos($block_name, 'regulatory') !== false) {
                if (!empty($change['new_block']['attrs']['paragraphs'][0]['number'])) {
                    $regulation_number = $change['new_block']['attrs']['paragraphs'][0]['number'];
                    $section = "Paragraph $regulation_number - $block_name";
                }
            }
        }
        
        // Default fallback
        if (empty($section)) {
            $section = 'General';
        }
        
        return $section;
    };
    
    // Organize changes by section
    foreach (['added', 'removed', 'amended'] as $type) {
        foreach ($changes[$type] as $change) {
            $section_key = $get_section_key($change);
            
            // Add section to order if not already included
            if (!in_array($section_key, $sections_order)) {
                $sections_order[] = $section_key;
            }
            
            // Initialize section if not already exists
            if (!isset($organized_changes[$section_key])) {
                $organized_changes[$section_key] = array(
                    'name' => $section_key,
                    'changes' => array(
                        'added' => array(),
                        'removed' => array(),
                        'amended' => array()
                    )
                );
            }
            
            // Add change to appropriate section and type
            $organized_changes[$section_key]['changes'][$type][] = $change;
        }
    }
    
    // Use sections order from changes if available, otherwise use our constructed one
    if (!empty($changes['sections_order'])) {
        $sections_order = array_merge($changes['sections_order'], array_diff($sections_order, $changes['sections_order']));
    }
    
    // Log sections for debugging
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log("Found " . count($organized_changes) . " sections for changelog $changelog_id");
        foreach ($organized_changes as $key => $section) {
            $added_count = count($section['changes']['added']);
            $removed_count = count($section['changes']['removed']);
            $amended_count = count($section['changes']['amended']);
            error_log("Section $key has $added_count added, $removed_count removed, $amended_count amended changes");
        }
    }
    
    // Update post meta with organized changes and summary
    update_post_meta($changelog_id, 'changes_data', $organized_changes);
    update_post_meta($changelog_id, 'sections_order', $sections_order);
    
    // Legacy format for backward compatibility
    $summary = array(
        'added' => count($changes['added']),
        'removed' => count($changes['removed']),
        'amended' => count($changes['amended'])
    );
    update_post_meta($changelog_id, 'changelog_summary', $summary);
    
    // Store formatted data specifically for the changelog block
    $changelog_block_data = array();
    foreach ($organized_changes as $section_key => $section) {
        $changelog_block_data[$section_key] = array(
            'name' => $section['name'],
            'changes' => array()
        );
        
        foreach (['added', 'removed', 'amended'] as $type) {
            foreach ($section['changes'][$type] as $change) {
                $content = '';
                
                // Determine content based on change type
                if ($type === 'added' && isset($change['content'])) {
                    $content = $change['content'];
                } else if ($type === 'removed' && isset($change['content'])) {
                    $content = $change['content'];
                } else if ($type === 'amended') {
                    if (isset($change['content'])) {
                        $content = $change['content'];
                    } else if (isset($change['old_content']) && isset($change['new_content'])) {
                        $content = "Changed from: " . $change['old_content'] . "\nTo: " . $change['new_content'];
                    }
                }
                
                $title = isset($change['title']) ? $change['title'] : '';
                if (empty($title)) {
                    // Try to generate a title
                    if (!empty($change['block']['blockName'])) {
                        $title = str_replace(['astp/', 'uswds-gutenberg/'], '', $change['block']['blockName']);
                        $title = ucwords(str_replace('-', ' ', $title));
                    } else if (!empty($change['new_block']['blockName'])) {
                        $title = str_replace(['astp/', 'uswds-gutenberg/'], '', $change['new_block']['blockName']);
                        $title = ucwords(str_replace('-', ' ', $title));
                    }
                }
                
                $changelog_block_data[$section_key]['changes'][] = array(
                    'type' => $type,
                    'title' => $title,
                    'content' => $content,
                    'blockType' => isset($change['block']['blockName']) ? $change['block']['blockName'] : 
                                 (isset($change['new_block']['blockName']) ? $change['new_block']['blockName'] : '')
                );
            }
        }
    }
    
    update_post_meta($changelog_id, 'changelog_block_data', $changelog_block_data);
    
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log("Stored changelog data for ID $changelog_id with " . count($organized_changes) . " sections");
    }
    
    return true;
}

/**
 * Roll back to a previous version
 *
 * @param int     $post_id    The post ID of the Test Method
 * @param int     $version_id The version ID to roll back to
 * @param string  $blocks     'all' or 'selected' to indicate which blocks to roll back
 * @return array  Result of the rollback operation
 */
function astp_rollback_to_version($post_id, $version_id, $blocks = 'all') {
    $result = [
        'success' => false,
        'message' => '',
        'version_id' => 0
    ];
    
    // Get version information
    $version_post = get_post($version_id);
    if (!$version_post) {
        $result['message'] = 'Version not found.';
        return $result;
    }
    
    // Make sure this is a version post type
    if ($version_post->post_type !== 'astp_version') {
        $result['message'] = 'Invalid version post type.';
        return $result;
    }
    
    // Get version data
    $version_number = get_post_meta($version_id, 'version_number', true);
    $parent_id = get_post_meta($version_id, 'parent_test_method', true);
    $version_date = get_post_meta($version_id, 'release_date', true);
    $version_type = get_post_meta($version_id, 'version_type', true);
    $original_author = get_post_meta($version_id, 'version_author', true);
    $rollback_version = get_post_meta($version_id, 'rollback_version', true);
    $document_type = get_post_meta($version_id, 'document_type', true); // Get document type
    
    // Validate parent test method matches
    if ((int)$parent_id !== (int)$post_id) {
        $result['message'] = 'Version does not belong to this Test Method.';
        return $result;
    }
    
    // Create a new rollback version post
    $current_user = wp_get_current_user();
    $rollback_version_id = wp_insert_post([
        'post_title' => 'Rollback to ' . $version_number,
        'post_status' => 'publish',
        'post_type' => 'astp_version',
        'post_author' => $current_user->ID,
        'post_content' => $version_post->post_content
    ]);
    
    if (is_wp_error($rollback_version_id)) {
        $result['message'] = 'Failed to create rollback version: ' . $rollback_version_id->get_error_message();
        return $result;
    }
    
    // Set rollback version meta
    update_post_meta($rollback_version_id, 'parent_test_method', $post_id);
    update_post_meta($rollback_version_id, 'version_number', $version_number);
    update_post_meta($rollback_version_id, 'version_type', 'rollback');
    update_post_meta($rollback_version_id, 'rollback_to_version', $version_id);
    update_post_meta($rollback_version_id, 'original_version_type', $version_type);
    update_post_meta($rollback_version_id, 'release_date', current_time('mysql'));
    update_post_meta($rollback_version_id, 'version_author', $current_user->ID);
    update_post_meta($rollback_version_id, 'original_author', $original_author);
    
    // Set document type if available
    if (!empty($document_type)) {
        update_post_meta($rollback_version_id, 'document_type', $document_type);
    }
    
    // Parse blocks from the version content
    $parsed_blocks = parse_blocks($version_post->post_content);
    
    // Store block snapshots with document type
    astp_store_block_snapshots($rollback_version_id, $parsed_blocks, $document_type);
    
    // Update previous version reference
    $latest_version_id = astp_get_latest_version_id($post_id);
    update_post_meta($rollback_version_id, 'previous_version', $latest_version_id);
    
    // Update the Test Method content
    if ($blocks === 'all') {
            wp_update_post([
                'ID' => $post_id,
            'post_content' => $version_post->post_content
        ]);
        } else {
        // Handle selective block rollback if needed
        // This would be a more complex implementation
    }
    
    // Update Test Method meta
    update_post_meta($post_id, 'current_version', $rollback_version_id);
    update_post_meta($post_id, 'current_version_number', $version_number);
    
    // Set result
    $result['success'] = true;
    $result['message'] = 'Successfully rolled back to version ' . $version_number;
    $result['version_id'] = $rollback_version_id;
    
    return $result;
}

/**
 * Get all versions of a content post
 *
 * @param int $post_id The ID of the parent post
 * @return array Array of version post objects, ordered by date (newest first)
 */
function astp_get_content_versions($post_id) {
    if (empty($post_id)) {
        return array();
    }
    
    // Query for versions related to this post
    $args = array(
        'post_type'      => 'astp_version',
        'posts_per_page' => -1,
        'meta_query'     => array(
            array(
                'key'   => 'parent_post_id',
                'value' => $post_id,
            ),
        ),
        'orderby'        => 'date',
        'order'          => 'DESC',
    );
    
    $versions = get_posts($args);
    
    // If no versions found with meta, try post_parent relationship
    if (empty($versions)) {
        $args = array(
            'post_type'      => 'astp_version',
            'posts_per_page' => -1,
            'post_parent'    => $post_id,
            'orderby'        => 'date',
            'order'          => 'DESC',
        );
        
        $versions = get_posts($args);
    }
    
    // Make sure versions have valid IDs and post types
    if (!empty($versions)) {
        foreach ($versions as $key => $version) {
            if (!is_object($version) || !isset($version->ID) || !is_numeric($version->ID) || $version->post_type !== 'astp_version') {
                unset($versions[$key]);
            }
        }
        
        // Reindex array
        $versions = array_values($versions);
    }
    
    return $versions;
}

/**
 * Get version changelog
 *
 * @param int $version_id The ID of the version post
 * @return WP_Post|bool The changelog post or false if none found
 */
function astp_get_version_changelog($version_id) {
    // Try to get changelog ID from version meta
    $changelog_id = get_post_meta($version_id, 'changelog_id', true);
    
    if (!empty($changelog_id)) {
        return get_post($changelog_id);
    }
    
    // If not found in meta, try querying for it
    $args = array(
        'post_type'      => 'astp_changelog',
        'posts_per_page' => 1,
        'meta_query'     => array(
            array(
                'key'   => 'version_id',
                'value' => $version_id,
            ),
        ),
    );
    
    $changelogs = get_posts($args);
        
        if (!empty($changelogs)) {
            return $changelogs[0];
    }
    
    return null;
}

/**
 * Log version events for debugging
 *
 * @param int    $version_id The ID of the version post
 * @param string $event      The event that occurred (created, published, etc.)
 */
function astp_log_version_event($version_id, $event) {
    if (!defined('WP_DEBUG') || !WP_DEBUG) {
        return;
    }
    
    $version_number = get_post_meta($version_id, 'version_number', true);
    $parent_id = wp_get_post_parent_id($version_id);
    $parent_title = get_the_title($parent_id);
    
    $message = sprintf(
        '[%s] Version event: %s - Version %s of "%s" (%d)',
        date('Y-m-d H:i:s'),
        $event,
        $version_number,
        $parent_title,
        $parent_id
    );
    
    error_log($message);
} 

/**
 * Get the changelog post for a specific version
 *
 * @param int $version_id ID of the version post
 * @return WP_Post|null The changelog post or null if not found
 */
function astp_get_changelog_for_version($version_id) {
    // First try to get from version meta
    $changelog_id = get_post_meta($version_id, 'changelog_id', true);
    
    if (!empty($changelog_id)) {
        $changelog = get_post($changelog_id);
        if ($changelog && $changelog->post_type === 'astp_changelog') {
            return $changelog;
        }
    }
    
    // If not found in meta, try to find by querying
    $args = array(
        'post_type' => 'astp_changelog',
        'posts_per_page' => 1,
        'meta_query' => array(
            array(
                'key' => 'version_id',
                'value' => $version_id,
                'compare' => '='
            )
        )
    );
    
    $query = new WP_Query($args);
    
    if ($query->have_posts()) {
        $changelog = $query->posts[0];
        
        // Save the ID in version meta for future lookups
        update_post_meta($version_id, 'changelog_id', $changelog->ID);
        
        return $changelog;
    }
    
    return null;
}

/**
 * Get a human-readable label for a document type
 *
 * @param string $document_type The document type code ('ccg' or 'tp')
 * @return string The human-readable label
 */
function astp_get_document_type_label($document_type) {
    switch ($document_type) {
        case 'ccg':
            return 'Certification Companion Guide';
        case 'tp':
            return 'Test Procedure';
        default:
            return ucfirst($document_type);
    }
}

/**
 * Get the parent post ID for a version
 *
 * @param int $version_id The ID of the version post
 * @return int|false The parent post ID or false if not found
 */
function astp_get_version_parent_id($version_id) {
    // Try to get directly from post meta
    $parent_id = get_post_meta($version_id, 'parent_post_id', true);
    
    if (!empty($parent_id)) {
        return $parent_id;
    }
    
    // If not found in meta, try to check if this is a revision of another post
    $version_post = get_post($version_id);
    
    if ($version_post && $version_post->post_parent) {
        return $version_post->post_parent;
    }
    
    return false;
}

/**
 * Get version history for specified document type and post ID
 *
 * @param string $doc_type Document type (ccg or tp)
 * @param int $post_id Post ID to get versions for
 * @return array Formatted version history data
 */
function astp_get_version_history($doc_type = 'ccg', $post_id = 0) {
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log("astp_get_version_history called for doc_type: $doc_type, post_id: $post_id");
    }
    
    if (!$post_id) {
        $post_id = get_the_ID();
    }
    
    // Query for versions of this post with the specified document type
    $args = array(
        'post_type' => 'astp_version',
        'posts_per_page' => -1,
        'post_parent' => $post_id,
        'meta_query' => array(
            array(
                'key' => 'document_type',
                'value' => $doc_type,
                'compare' => '=',
            ),
            array(
                'key' => 'version_number',
                'compare' => 'EXISTS',
            ),
        ),
        'tax_query' => array(
            array(
                'taxonomy' => 'version_status',
                'field' => 'slug',
                'terms' => 'published',
            ),
        ),
        'meta_key' => 'version_number',
        'orderby' => 'meta_value',
        'order' => 'DESC',
    );
    
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log("Version query args: " . print_r($args, true));
    }
    
    $version_posts = get_posts($args);
    
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log("Found " . count($version_posts) . " version posts");
        
        foreach ($version_posts as $index => $post) {
            error_log("Version post $index - ID: {$post->ID}, Title: {$post->post_title}");
        }
    }
    
    if (empty($version_posts)) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("No $doc_type versions found for post ID: $post_id");
            
            // Try a more basic query to see if there are any versions at all
            $basic_args = array(
                'post_type' => 'astp_version',
                'posts_per_page' => -1,
                'post_status' => 'any',
            );
            $all_versions = get_posts($basic_args);
            
            error_log("Basic query found " . count($all_versions) . " version posts");
            
            foreach ($all_versions as $v) {
                $ver_doc_type = get_post_meta($v->ID, 'document_type', true);
                $ver_number = get_post_meta($v->ID, 'version_number', true);
                
                // Get version status
                $status_terms = wp_get_post_terms($v->ID, 'version_status');
                $status = !empty($status_terms) ? $status_terms[0]->slug : '';
                
                error_log("Version ID: {$v->ID}, Title: {$v->post_title}, Document Type: {$ver_doc_type}, Version Number: {$ver_number}, Status: {$status}");
            }
        }
        return array();
    }
    
    $formatted_versions = array();
    
    foreach ($version_posts as $version_post) {
        $version_id = $version_post->ID;
        $version_number = get_post_meta($version_id, 'version_number', true);
        $version_date = get_post_meta($version_id, 'version_date', true);
        
        if (empty($version_date)) {
            $version_date = get_the_date('Y-m-d', $version_id);
        }
        
        // Get changelog ID
        $changelog_id = get_post_meta($version_id, 'changelog_id', true);
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("Processing version ID: $version_id, Number: $version_number, Date: $version_date, Changelog ID: $changelog_id");
        }
        
        $version_data = array(
            'version_id' => $version_id,
            'version' => $version_number,
            'date' => $version_date,
            'changes' => array(),
        );
        
        if ($changelog_id) {
            $version_data['changelog_id'] = $changelog_id;
            
            // Get the changes data for this changelog
            $changes_data = get_post_meta($changelog_id, 'changes_data', true);
            
            if (!empty($changes_data)) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log("Found changes_data for changelog ID: $changelog_id");
                    error_log("Changes_data format: " . print_r(array_keys($changes_data), true));
                    
                    // Add more detailed debugging for the structure
                    error_log("DETAILED CHANGES DATA FOR CHANGELOG ID: $changelog_id");
                    
                    // Check if added changes exist and examine them
                    if (isset($changes_data['added']) && is_array($changes_data['added'])) {
                        error_log("ADDED CHANGES COUNT: " . count($changes_data['added']));
                        if (!empty($changes_data['added'])) {
                            $sample = $changes_data['added'][0];
                            error_log("SAMPLE ADDED CHANGE STRUCTURE: " . print_r($sample, true));
                            
                            // Debug specific parts that might be problematic
                            if (isset($sample['section'])) {
                                error_log("SECTION FORMAT: " . print_r($sample['section'], true));
                            }
                            if (isset($sample['block'])) {
                                error_log("BLOCK FORMAT: " . print_r(array_keys($sample['block']), true));
                                if (isset($sample['block']['attrs'])) {
                                    error_log("BLOCK ATTRS: " . print_r(array_keys($sample['block']['attrs']), true));
                                }
                            }
                        }
                    }
                    
                    // Check if removed changes exist
                    if (isset($changes_data['removed']) && is_array($changes_data['removed'])) {
                        error_log("REMOVED CHANGES COUNT: " . count($changes_data['removed']));
                        if (!empty($changes_data['removed'])) {
                            error_log("SAMPLE REMOVED CHANGE STRUCTURE: " . print_r($changes_data['removed'][0], true));
                        }
                    }
                    
                    // Check if amended changes exist
                    if (isset($changes_data['amended']) && is_array($changes_data['amended'])) {
                        error_log("AMENDED CHANGES COUNT: " . count($changes_data['amended']));
                        if (!empty($changes_data['amended'])) {
                            error_log("SAMPLE AMENDED CHANGE STRUCTURE: " . print_r($changes_data['amended'][0], true));
                        }
                    }
                }
                
                // Get structured changes data based on sections if available
                $block_data = get_post_meta($changelog_id, 'changelog_block_data', true);
                
                if (!empty($block_data) && is_array($block_data)) {
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log("Found changelog_block_data with structure: " . print_r(array_keys($block_data), true));
                        
                        // Sample section
                        $section_keys = array_keys($block_data);
                        if (!empty($section_keys)) {
                            $first_section = $block_data[$section_keys[0]];
                            error_log("SAMPLE SECTION STRUCTURE: " . print_r($first_section, true));
                        }
                    }
                    
                    // Process each section with its changes
                    foreach ($block_data as $section_key => $section) {
                        if (!empty($section['changes']) && is_array($section['changes'])) {
                            foreach ($section['changes'] as $change) {
                                if (isset($change['type']) && isset($change['content'])) {
                                    $formatted_change = array(
                                        'type' => $change['type'],
                                        'section' => isset($section['name']) ? $section['name'] : $section_key,
                                        'title' => isset($change['title']) ? $change['title'] : '',
                                        'content' => $change['content'],
                                    );
                                    
                                    $version_data['changes'][] = $formatted_change;
                                }
                            }
                        }
                    }
                } else {
                    // If no structured block data is found, process the raw changes data
                    
                    // Process added changes
                    if (!empty($changes_data['added']) && is_array($changes_data['added'])) {
                        foreach ($changes_data['added'] as $change) {
                            $section_name = isset($change['section']) && isset($change['section']['full_text']) 
                                ? $change['section']['full_text'] 
                                : 'General';
                                
                            $title = isset($change['section']) && isset($change['section']['title']) 
                                ? $change['section']['title'] 
                                : '';
                            
                            // Extract content from the block
                            $content = '';
                            if (isset($change['block'])) {
                                // For regulatory blocks with paragraphs
                                if (isset($change['block']['attrs']) && isset($change['block']['attrs']['paragraphs'])) {
                                    $content = '<div class="regulatory-paragraph-content">';
                                    foreach ($change['block']['attrs']['paragraphs'] as $paragraph) {
                                        $content .= '<div class="regulatory-paragraph">';
                                        $content .= '<strong>Paragraph ' . esc_html($paragraph['number']) . ':</strong> ';
                                        $content .= wp_kses_post($paragraph['text']);
                                        $content .= '</div>';
                                    }
                                    $content .= '</div>';
                                }
                                // Fallback to innerHTML
                                else if (isset($change['block']['innerHTML'])) {
                                    $content = wp_kses_post($change['block']['innerHTML']);
                                }
                            }
                            
                            $formatted_change = array(
                                'type' => 'added',
                                'section' => $section_name,
                                'title' => $title,
                                'content' => $content,
                            );
                            
                            $version_data['changes'][] = $formatted_change;
                        }
                    }
                    
                    // Process removed changes
                    if (!empty($changes_data['removed']) && is_array($changes_data['removed'])) {
                        foreach ($changes_data['removed'] as $change) {
                            $section_name = isset($change['section']) && isset($change['section']['full_text']) 
                                ? $change['section']['full_text'] 
                                : 'General';
                                
                            $title = isset($change['section']) && isset($change['section']['title']) 
                                ? $change['section']['title'] 
                                : '';
                            
                            // Extract content from the block
                            $content = '';
                            if (isset($change['block'])) {
                                // For regulatory blocks with paragraphs
                                if (isset($change['block']['attrs']) && isset($change['block']['attrs']['paragraphs'])) {
                                    $content = '<div class="regulatory-paragraph-content">';
                                    foreach ($change['block']['attrs']['paragraphs'] as $paragraph) {
                                        $content .= '<div class="regulatory-paragraph">';
                                        $content .= '<strong>Paragraph ' . esc_html($paragraph['number']) . ':</strong> ';
                                        $content .= wp_kses_post($paragraph['text']);
                                        $content .= '</div>';
                                    }
                                    $content .= '</div>';
                                }
                                // Fallback to innerHTML
                                else if (isset($change['block']['innerHTML'])) {
                                    $content = wp_kses_post($change['block']['innerHTML']);
                                }
                            }
                            
                            $formatted_change = array(
                                'type' => 'removed',
                                'section' => $section_name,
                                'title' => $title,
                                'content' => $content,
                            );
                            
                            $version_data['changes'][] = $formatted_change;
                        }
                    }
                    
                    // Process amended changes
                    if (!empty($changes_data['amended']) && is_array($changes_data['amended'])) {
                        foreach ($changes_data['amended'] as $change) {
                            $section_name = isset($change['section']) && isset($change['section']['full_text']) 
                                ? $change['section']['full_text'] 
                                : 'General';
                                
                            $title = isset($change['section']) && isset($change['section']['title']) 
                                ? $change['section']['title'] 
                                : '';
                            
                            // For amended changes, create side-by-side diff display
                            $content = '<div class="diff-container">';
                            
                            // Old content
                            if (isset($change['old_block'])) {
                                $content .= '<div class="diff-old">';
                                $content .= '<h5>Previous Version:</h5>';
                                
                                if (isset($change['old_block']['attrs']) && isset($change['old_block']['attrs']['paragraphs'])) {
                                    foreach ($change['old_block']['attrs']['paragraphs'] as $paragraph) {
                                        $content .= '<div class="regulatory-paragraph">';
                                        $content .= '<strong>Paragraph ' . esc_html($paragraph['number']) . ':</strong> ';
                                        $content .= wp_kses_post($paragraph['text']);
                                        $content .= '</div>';
                                    }
                                } else if (isset($change['old_block']['innerHTML'])) {
                                    $content .= wp_kses_post($change['old_block']['innerHTML']);
                                }
                                
                                $content .= '</div>';
                            }
                            
                            // New content
                            if (isset($change['new_block'])) {
                                $content .= '<div class="diff-new">';
                                $content .= '<h5>Updated to:</h5>';
                                
                                if (isset($change['new_block']['attrs']) && isset($change['new_block']['attrs']['paragraphs'])) {
                                    foreach ($change['new_block']['attrs']['paragraphs'] as $paragraph) {
                                        $content .= '<div class="regulatory-paragraph">';
                                        $content .= '<strong>Paragraph ' . esc_html($paragraph['number']) . ':</strong> ';
                                        $content .= wp_kses_post($paragraph['text']);
                                        $content .= '</div>';
                                    }
                                } else if (isset($change['new_block']['innerHTML'])) {
                                    $content .= wp_kses_post($change['new_block']['innerHTML']);
                                }
                                
                                $content .= '</div>';
                            }
                            
                            $content .= '</div>';
                            
                            $formatted_change = array(
                                'type' => 'amended',
                                'section' => $section_name,
                                'title' => $title,
                                'content' => $content,
                            );
                            
                            $version_data['changes'][] = $formatted_change;
                        }
                    }
                }
            }
        }
        
        $formatted_versions[] = $version_data;
    }
    
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log("Returning " . count($formatted_versions) . " formatted versions with data");
    }
    
    return $formatted_versions;
}

/**
 * Get the original post ID for a version.
 *
 * @param int $version_id The version post ID
 * @return int|bool The original post ID or false
 */
function astp_get_post_id_for_version($version_id) {
    // Check if we have parent post info in meta
    $post_id = get_post_meta($version_id, 'post_id', true);
    
    if ($post_id) {
        return $post_id;
    }
    
    // If not found in meta, try to check if this is a revision of another post
    $version_post = get_post($version_id);
    
    if ($version_post && $version_post->post_parent) {
        return $version_post->post_parent;
    }
    
    return false;
}


<?php
/**
 * Debug Helper File
 * 
 * This file adds functions to help debug the versioning system
 * 
 * @package ASTP_Versioning
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Add an admin notice for debugging
 */
function astp_debug_admin_notice() {
    // Only show on version edit screens
    $screen = get_current_screen();
    if (!$screen || $screen->post_type !== 'astp_version') {
        return;
    }
    
    $post_id = isset($_GET['post']) ? intval($_GET['post']) : 0;
    if (!$post_id) {
        return;
    }
    
    ?>
    <div class="notice notice-info">
        <p><strong>Debug Mode Active:</strong> Changes are being logged for diagnosis. <a href="<?php echo esc_url(admin_url('admin.php?page=astp-debug-info&version=' . $post_id)); ?>">View Debug Info</a></p>
    </div>
    <?php
}
add_action('admin_notices', 'astp_debug_admin_notice');

/**
 * Register debug menu
 */
function astp_register_debug_menu() {
    add_submenu_page(
        null, // Hide from menu
        'ASTP Debug Info',
        'Debug Info',
        'manage_options',
        'astp-debug-info',
        'astp_debug_info_page'
    );
}
add_action('admin_menu', 'astp_register_debug_menu');

/**
 * Debug info page
 */
function astp_debug_info_page() {
    $version_id = isset($_GET['version']) ? intval($_GET['version']) : 0;
    if (!$version_id) {
        wp_die('No version specified');
    }
    
    $version = get_post($version_id);
    if (!$version || $version->post_type !== 'astp_version') {
        wp_die('Invalid version');
    }
    
    // Get version data
    $version_number = get_post_meta($version_id, 'version_number', true);
    $previous_version_id = get_post_meta($version_id, 'previous_version_id', true);
    $block_snapshots = get_post_meta($version_id, 'block_snapshots', true);
    $changelog_id = 0;
    
    // Find changelog
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
    
    $changelogs = get_posts($args);
    if (!empty($changelogs)) {
        $changelog_id = $changelogs[0]->ID;
    }
    
    // Get changes data
    $changes_data = $changelog_id ? get_post_meta($changelog_id, 'changes_data', true) : array();
    
    ?>
    <div class="wrap">
        <h1>Debug Info for Version <?php echo esc_html($version_number); ?></h1>
        
        <h2>Version Details</h2>
        <table class="widefat">
            <tr>
                <th>Version ID</th>
                <td><?php echo $version_id; ?></td>
            </tr>
            <tr>
                <th>Version Number</th>
                <td><?php echo esc_html($version_number); ?></td>
            </tr>
            <tr>
                <th>Previous Version ID</th>
                <td><?php echo $previous_version_id ? $previous_version_id : 'None'; ?></td>
            </tr>
            <tr>
                <th>Changelog ID</th>
                <td><?php echo $changelog_id ? $changelog_id : 'None'; ?></td>
            </tr>
        </table>
        
        <h2>Block Snapshots</h2>
        <?php if (is_array($block_snapshots)) : ?>
            <p>Found <?php echo count($block_snapshots); ?> blocks in snapshot.</p>
            <table class="widefat">
                <thead>
                    <tr>
                        <th>Block ID</th>
                        <th>Block Name</th>
                        <th>Attributes</th>
                        <th>Content Sample</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($block_snapshots as $block_id => $block) : ?>
                        <tr>
                            <td><?php echo esc_html($block_id); ?></td>
                            <td><?php echo esc_html($block['blockName'] ?? 'Unknown'); ?></td>
                            <td>
                                <?php 
                                if (isset($block['attrs']) && is_array($block['attrs'])) {
                                    echo '<pre>' . esc_html(json_encode($block['attrs'], JSON_PRETTY_PRINT)) . '</pre>';
                                } else {
                                    echo 'No attributes';
                                }
                                ?>
                            </td>
                            <td>
                                <?php 
                                if (isset($block['innerHTML'])) {
                                    echo esc_html(substr($block['innerHTML'], 0, 100)) . (strlen($block['innerHTML']) > 100 ? '...' : '');
                                } else {
                                    echo 'No content';
                                }
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else : ?>
            <p>No block snapshots found or invalid format: <?php echo gettype($block_snapshots); ?></p>
        <?php endif; ?>
        
        <h2>Changes Data</h2>
        <?php if (is_array($changes_data)) : ?>
            <h3>Added Changes (<?php echo !empty($changes_data['added']) ? count($changes_data['added']) : 0; ?>)</h3>
            <?php if (!empty($changes_data['added'])) : ?>
                <table class="widefat">
                    <thead>
                        <tr>
                            <th>Section</th>
                            <th>Block Type</th>
                            <th>Content Sample</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($changes_data['added'] as $change) : ?>
                            <tr>
                                <td><?php echo esc_html($change['section']['reference'] ?? '') . ' ' . esc_html($change['section']['title'] ?? 'General'); ?></td>
                                <td><?php echo esc_html($change['block']['blockName'] ?? 'Unknown'); ?></td>
                                <td>
                                    <?php 
                                    if (isset($change['block']['innerHTML'])) {
                                        echo esc_html(substr($change['block']['innerHTML'], 0, 100)) . (strlen($change['block']['innerHTML']) > 100 ? '...' : '');
                                    } else {
                                        echo 'No content';
                                    }
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <p>No added changes.</p>
            <?php endif; ?>
            
            <h3>Removed Changes (<?php echo !empty($changes_data['removed']) ? count($changes_data['removed']) : 0; ?>)</h3>
            <?php if (!empty($changes_data['removed'])) : ?>
                <table class="widefat">
                    <thead>
                        <tr>
                            <th>Section</th>
                            <th>Block Type</th>
                            <th>Content Sample</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($changes_data['removed'] as $change) : ?>
                            <tr>
                                <td><?php echo esc_html($change['section']['reference'] ?? '') . ' ' . esc_html($change['section']['title'] ?? 'General'); ?></td>
                                <td><?php echo esc_html($change['block']['blockName'] ?? 'Unknown'); ?></td>
                                <td>
                                    <?php 
                                    if (isset($change['block']['innerHTML'])) {
                                        echo esc_html(substr($change['block']['innerHTML'], 0, 100)) . (strlen($change['block']['innerHTML']) > 100 ? '...' : '');
                                    } else {
                                        echo 'No content';
                                    }
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <p>No removed changes.</p>
            <?php endif; ?>
            
            <h3>Amended Changes (<?php echo !empty($changes_data['amended']) ? count($changes_data['amended']) : 0; ?>)</h3>
            <?php if (!empty($changes_data['amended'])) : ?>
                <table class="widefat">
                    <thead>
                        <tr>
                            <th>Section</th>
                            <th>Block Type</th>
                            <th>Old Content</th>
                            <th>New Content</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($changes_data['amended'] as $change) : ?>
                            <tr>
                                <td><?php echo esc_html($change['section']['reference'] ?? '') . ' ' . esc_html($change['section']['title'] ?? 'General'); ?></td>
                                <td><?php echo esc_html($change['new_block']['blockName'] ?? 'Unknown'); ?></td>
                                <td>
                                    <?php 
                                    if (isset($change['old_block']['innerHTML'])) {
                                        echo esc_html(substr($change['old_block']['innerHTML'], 0, 100)) . (strlen($change['old_block']['innerHTML']) > 100 ? '...' : '');
                                    } else {
                                        echo 'No content';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php 
                                    if (isset($change['new_block']['innerHTML'])) {
                                        echo esc_html(substr($change['new_block']['innerHTML'], 0, 100)) . (strlen($change['new_block']['innerHTML']) > 100 ? '...' : '');
                                    } else {
                                        echo 'No content';
                                    }
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <p>No amended changes.</p>
            <?php endif; ?>
            
            <h3>Section Order</h3>
            <?php if (!empty($changes_data['sections_order'])) : ?>
                <ol>
                    <?php foreach ($changes_data['sections_order'] as $section) : ?>
                        <li><?php echo esc_html($section); ?></li>
                    <?php endforeach; ?>
                </ol>
            <?php else : ?>
                <p>No section order data.</p>
            <?php endif; ?>
        <?php else : ?>
            <p>No changes data found or invalid format: <?php echo gettype($changes_data); ?></p>
        <?php endif; ?>
        
        <h2>Debug Actions</h2>
        <form method="post" action="">
            <?php wp_nonce_field('astp_debug_actions', 'astp_debug_nonce'); ?>
            <input type="hidden" name="version_id" value="<?php echo $version_id; ?>">
            
            <p>
                <button type="submit" name="action" value="regenerate_changelog" class="button button-primary">
                    Regenerate Changelog
                </button>
                
                <button type="submit" name="action" value="force_update" class="button">
                    Force Update Block Snapshots
                </button>
            </p>
        </form>
    </div>
    <?php
    
    // Handle actions
    if (isset($_POST['action']) && isset($_POST['astp_debug_nonce']) && wp_verify_nonce($_POST['astp_debug_nonce'], 'astp_debug_actions')) {
        $action = $_POST['action'];
        $version_id = isset($_POST['version_id']) ? intval($_POST['version_id']) : 0;
        
        if ($action === 'regenerate_changelog' && $version_id) {
            // Get previous version
            $previous_version_id = get_post_meta($version_id, 'previous_version_id', true);
            
            if ($previous_version_id) {
                // Delete existing changelog
                if ($changelog_id) {
                    wp_delete_post($changelog_id, true);
                }
                
                // Regenerate changelog
                astp_create_changelog($version_id, $previous_version_id);
                
                echo '<div class="notice notice-success"><p>Changelog regenerated. <a href="' . esc_url($_SERVER['REQUEST_URI']) . '">Refresh</a> to see the results.</p></div>';
            } else {
                echo '<div class="notice notice-error"><p>Cannot regenerate changelog: No previous version found.</p></div>';
            }
        } elseif ($action === 'force_update' && $version_id) {
            // Get parent post
            $parent_id = wp_get_post_parent_id($version_id);
            
            if ($parent_id) {
                // Force update block snapshots
                astp_store_block_snapshots($parent_id, $version_id);
                
                echo '<div class="notice notice-success"><p>Block snapshots updated. <a href="' . esc_url($_SERVER['REQUEST_URI']) . '">Refresh</a> to see the results.</p></div>';
            } else {
                echo '<div class="notice notice-error"><p>Cannot update block snapshots: No parent post found.</p></div>';
            }
        }
    }
}

/**
 * Include this file from main plugin
 */
function astp_load_debug_helper() {
    // This function is just a placeholder - the file is included by being loaded
    error_log("DEBUG HELPER: Debug Helper loaded");
}
add_action('plugins_loaded', 'astp_load_debug_helper');

if (defined('WP_DEBUG') && WP_DEBUG) {
    error_log("DEBUG HELPER: Debug Helper loaded");
}

/**
 * Creates a debug shortcode for displaying raw changelog data
 */
function astp_changelog_debug_shortcode($atts) {
    $atts = shortcode_atts(array(
        'post_id' => get_the_ID(),
        'changelog_id' => 0,
        'doc_type' => 'ccg'
    ), $atts, 'astp_changelog_debug');
    
    $post_id = intval($atts['post_id']);
    $changelog_id = intval($atts['changelog_id']);
    $doc_type = $atts['doc_type'];
    
    ob_start();
    
    echo '<div class="astp-debug-container" style="background: #f8f8f8; padding: 20px; margin: 20px 0; border: 1px solid #ddd; font-family: monospace;">';
    echo '<h2>Changelog Debug for Post ID: ' . esc_html($post_id) . '</h2>';
    
    if (!$changelog_id) {
        // Try to find a changelog based on the most recent version
        $versions = astp_get_version_history($doc_type, $post_id);
        
        if (!empty($versions) && isset($versions[0]['changelog_id'])) {
            $changelog_id = $versions[0]['changelog_id'];
            echo '<p>Using most recent changelog ID: ' . esc_html($changelog_id) . '</p>';
        } else {
            echo '<p>No changelog ID found for this post.</p>';
            
            // Show version information anyway
            echo '<h3>Version Information</h3>';
            if (empty($versions)) {
                echo '<p>No versions found for document type: ' . esc_html($doc_type) . '</p>';
            } else {
                echo '<p>Found ' . count($versions) . ' versions:</p>';
                echo '<ul>';
                foreach ($versions as $version) {
                    echo '<li>';
                    echo 'Version ' . esc_html($version['version']) . ' (ID: ' . esc_html($version['version_id']) . ')';
                    echo isset($version['changelog_id']) ? ' - Has changelog: ' . esc_html($version['changelog_id']) : ' - No changelog';
                    echo '</li>';
                }
                echo '</ul>';
            }
            
            echo '</div>';
            return ob_get_clean();
        }
    }
    
    // Get all changelog metadata
    echo '<h3>Changelog Meta Keys for ID: ' . esc_html($changelog_id) . '</h3>';
    $meta_keys = get_post_custom_keys($changelog_id);
    
    if (empty($meta_keys)) {
        echo '<p>No metadata found for this changelog.</p>';
    } else {
        echo '<ul>';
        foreach ($meta_keys as $key) {
            echo '<li>' . esc_html($key) . '</li>';
        }
        echo '</ul>';
    }
    
    // Get specific meta values
    $important_keys = array(
        'changes_data',
        'changelog_block_data',
        'raw_changes_data',
        'changelog_changes',
        'version_id',
        'previous_version_id'
    );
    
    echo '<h3>Important Changelog Data</h3>';
    
    foreach ($important_keys as $key) {
        $value = get_post_meta($changelog_id, $key, true);
        echo '<h4>Meta Key: ' . esc_html($key) . '</h4>';
        
        if (empty($value)) {
            echo '<p>No data found.</p>';
        } else {
            echo '<div style="background: #fff; padding: 10px; overflow: auto; max-height: 300px; border: 1px solid #ddd; margin-bottom: 20px;">';
            echo '<pre>';
            print_r($value);
            echo '</pre>';
            echo '</div>';
            
            // Additional analysis for changes_data
            if ($key === 'changes_data' && is_array($value)) {
                echo '<h5>Changes Data Analysis</h5>';
                
                // Check primary structure
                echo '<p>Changes data contains the following keys: ';
                echo implode(', ', array_keys($value));
                echo '</p>';
                
                // Check for each type of change
                foreach (['added', 'removed', 'amended'] as $change_type) {
                    if (isset($value[$change_type]) && is_array($value[$change_type])) {
                        echo '<p><strong>' . ucfirst($change_type) . ' changes:</strong> ' . count($value[$change_type]) . '</p>';
                        
                        if (!empty($value[$change_type])) {
                            // Show sample
                            $sample = $value[$change_type][0];
                            echo '<div style="margin-left: 20px;">';
                            echo '<p>Sample ' . $change_type . ' change structure:</p>';
                            echo '<ul>';
                            
                            foreach (array_keys($sample) as $prop) {
                                echo '<li>' . esc_html($prop) . '</li>';
                            }
                            
                            echo '</ul>';
                            
                            // Show section details
                            if (isset($sample['section'])) {
                                echo '<p>Section structure:</p>';
                                echo '<ul>';
                                
                                if (is_array($sample['section'])) {
                                    foreach (array_keys($sample['section']) as $prop) {
                                        echo '<li>' . esc_html($prop) . ': ';
                                        
                                        if (is_scalar($sample['section'][$prop])) {
                                            echo esc_html($sample['section'][$prop]);
                                        } else {
                                            echo '[complex data]';
                                        }
                                        
                                        echo '</li>';
                                    }
                                } else {
                                    echo '<li>Section is not an array: ' . esc_html($sample['section']) . '</li>';
                                }
                                
                                echo '</ul>';
                            }
                            
                            // Show block details
                            if (isset($sample['block'])) {
                                echo '<p>Block structure:</p>';
                                echo '<ul>';
                                
                                if (is_array($sample['block'])) {
                                    foreach (array_keys($sample['block']) as $prop) {
                                        echo '<li>' . esc_html($prop) . '</li>';
                                    }
                                    
                                    // Show block attributes if available
                                    if (isset($sample['block']['attrs']) && is_array($sample['block']['attrs'])) {
                                        echo '<li>attrs contains: ';
                                        echo implode(', ', array_keys($sample['block']['attrs']));
                                        echo '</li>';
                                    }
                                } else {
                                    echo '<li>Block is not an array: ' . esc_html($sample['block']) . '</li>';
                                }
                                
                                echo '</ul>';
                            }
                            
                            echo '</div>';
                        }
                    }
                }
            }
        }
    }
    
    // Show the post itself
    $changelog_post = get_post($changelog_id);
    if ($changelog_post) {
        echo '<h3>Changelog Post</h3>';
        echo '<div style="background: #fff; padding: 10px; overflow: auto; max-height: 300px; border: 1px solid #ddd; margin-bottom: 20px;">';
        echo '<pre>';
        print_r($changelog_post);
        echo '</pre>';
        echo '</div>';
    }
    
    echo '</div>';
    
    return ob_get_clean();
}
add_shortcode('astp_changelog_debug', 'astp_changelog_debug_shortcode');

/**
 * Creates a shortcode for displaying changelog data in the block format
 */
function astp_changelog_preview_shortcode($atts) {
    $atts = shortcode_atts(array(
        'post_id' => get_the_ID(),
        'doc_type' => 'ccg',
        'version_id' => 0,
        'changelog_id' => 0,
    ), $atts, 'astp_changelog_preview');
    
    $post_id = intval($atts['post_id']);
    $doc_type = $atts['doc_type'];
    $version_id = intval($atts['version_id']);
    $changelog_id = intval($atts['changelog_id']);
    
    // Find changelog ID if only version ID is provided
    if ($version_id && !$changelog_id) {
        $changelog_id = get_post_meta($version_id, 'changelog_id', true);
    }
    
    // Find most recent version if no version is specified
    if (!$version_id && !$changelog_id) {
        $versions = astp_get_version_history($doc_type, $post_id);
        if (!empty($versions)) {
            $version_id = $versions[0]['version_id'];
            $changelog_id = isset($versions[0]['changelog_id']) ? $versions[0]['changelog_id'] : 0;
        }
    }
    
    if (!$changelog_id) {
        return '<p>No changelog found. Please specify a valid changelog ID or version ID.</p>';
    }
    
    ob_start();
    
    // Get the changes data
    $changes_data = get_post_meta($changelog_id, 'changes_data', true);
    $structured_data = get_post_meta($changelog_id, 'changelog_block_data', true);
    
    echo '<div style="padding: 20px; background: #f9f9f9; border: 1px solid #ddd; margin: 20px 0;">';
    echo '<h2>Changelog Preview for ID: ' . esc_html($changelog_id) . '</h2>';
    
    // Try to display structured changelog data first
    if (!empty($structured_data) && is_array($structured_data)) {
        echo '<div class="changelog-structured-preview">';
        echo '<h3>Structured Changelog Data</h3>';
        
        foreach ($structured_data as $section_key => $section) {
            echo '<div class="change-section" style="margin-bottom: 20px; border-bottom: 1px solid #ddd; padding-bottom: 15px;">';
            echo '<h4>' . (isset($section['name']) ? esc_html($section['name']) : esc_html($section_key)) . '</h4>';
            
            if (!empty($section['changes']) && is_array($section['changes'])) {
                echo '<ul style="list-style: none; padding-left: 0;">';
                
                foreach ($section['changes'] as $change) {
                    $type = isset($change['type']) ? $change['type'] : 'unknown';
                    $title = isset($change['title']) ? $change['title'] : '';
                    $content = isset($change['content']) ? $change['content'] : '';
                    
                    echo '<li style="margin-bottom: 15px; padding-left: 20px; border-left: 4px solid ';
                    
                    // Color-code by type
                    switch ($type) {
                        case 'added':
                            echo '#4CAF50';
                            break;
                        case 'removed':
                            echo '#F44336';
                            break;
                        case 'amended':
                            echo '#FF9800';
                            break;
                        default:
                            echo '#2196F3';
                    }
                    
                    echo ';">';
                    
                    echo '<div style="font-weight: bold; margin-bottom: 5px;">';
                    echo '<span style="text-transform: uppercase; font-size: 12px; background: #eee; padding: 2px 6px; border-radius: 3px; margin-right: 10px;">';
                    echo esc_html(ucfirst($type));
                    echo '</span>';
                    
                    if (!empty($title)) {
                        echo esc_html($title);
                    }
                    
                    echo '</div>';
                    
                    echo '<div style="margin-top: 5px;">';
                    echo wp_kses_post($content);
                    echo '</div>';
                    
                    echo '</li>';
                }
                
                echo '</ul>';
            } else {
                echo '<p>No changes in this section.</p>';
            }
            
            echo '</div>';
        }
        
        echo '</div>';
    } else if (!empty($changes_data) && is_array($changes_data)) {
        // Display raw changes data if structured data isn't available
        echo '<div class="changelog-raw-preview">';
        echo '<h3>Raw Changes Data</h3>';
        
        // Check if using the standard format with added/removed/amended as top keys
        $standard_format = isset($changes_data['added']) || isset($changes_data['removed']) || isset($changes_data['amended']);
        
        if ($standard_format) {
            // Process each change type
            foreach (['added', 'removed', 'amended'] as $change_type) {
                if (isset($changes_data[$change_type]) && is_array($changes_data[$change_type]) && !empty($changes_data[$change_type])) {
                    echo '<div class="changes-group" style="margin-bottom: 20px;">';
                    echo '<h4 style="color: ';
                    
                    // Color-code by type
                    switch ($change_type) {
                        case 'added':
                            echo '#4CAF50';
                            break;
                        case 'removed':
                            echo '#F44336';
                            break;
                        case 'amended':
                            echo '#FF9800';
                            break;
                    }
                    
                    echo ';">' . ucfirst($change_type) . ' Changes (' . count($changes_data[$change_type]) . ')</h4>';
                    
                    // Group by section
                    $changes_by_section = [];
                    foreach ($changes_data[$change_type] as $change) {
                        $section = 'General';
                        
                        if (isset($change['section'])) {
                            if (is_array($change['section']) && isset($change['section']['full_text'])) {
                                $section = $change['section']['full_text'];
                            } else if (is_string($change['section'])) {
                                $section = $change['section'];
                            }
                        }
                        
                        if (!isset($changes_by_section[$section])) {
                            $changes_by_section[$section] = [];
                        }
                        
                        $changes_by_section[$section][] = $change;
                    }
                    
                    // Display each section
                    foreach ($changes_by_section as $section => $section_changes) {
                        echo '<div class="section-group" style="margin-left: 20px; margin-bottom: 15px; border-left: 1px solid #ddd; padding-left: 15px;">';
                        echo '<h5>' . esc_html($section) . '</h5>';
                        
                        foreach ($section_changes as $change) {
                            echo '<div style="margin-bottom: 10px; background: #fff; padding: 10px; border: 1px solid #eee;">';
                            
                            // Display content based on the type of block
                            if ($change_type === 'amended' && isset($change['old_block']) && isset($change['new_block'])) {
                                echo '<div style="display: flex; flex-wrap: wrap; gap: 20px;">';
                                
                                // Old content
                                echo '<div style="flex: 1; min-width: 300px; border: 1px solid #ddd; padding: 10px; background: #fff8f8;">';
                                echo '<h6 style="margin-top: 0;">Previous Version</h6>';
                                
                                if (isset($change['old_block']['attrs']) && isset($change['old_block']['attrs']['paragraphs'])) {
                                    foreach ($change['old_block']['attrs']['paragraphs'] as $para) {
                                        echo '<div style="margin-bottom: 10px;">';
                                        echo '<strong>Paragraph ' . (isset($para['number']) ? esc_html($para['number']) : '') . ':</strong> ';
                                        echo wp_kses_post($para['text']);
                                        echo '</div>';
                                    }
                                } else if (isset($change['old_block']['innerHTML'])) {
                                    echo wp_kses_post($change['old_block']['innerHTML']);
                                } else {
                                    echo '<p>No content available</p>';
                                }
                                
                                echo '</div>';
                                
                                // New content
                                echo '<div style="flex: 1; min-width: 300px; border: 1px solid #ddd; padding: 10px; background: #f8fff8;">';
                                echo '<h6 style="margin-top: 0;">Updated To</h6>';
                                
                                if (isset($change['new_block']['attrs']) && isset($change['new_block']['attrs']['paragraphs'])) {
                                    foreach ($change['new_block']['attrs']['paragraphs'] as $para) {
                                        echo '<div style="margin-bottom: 10px;">';
                                        echo '<strong>Paragraph ' . (isset($para['number']) ? esc_html($para['number']) : '') . ':</strong> ';
                                        echo wp_kses_post($para['text']);
                                        echo '</div>';
                                    }
                                } else if (isset($change['new_block']['innerHTML'])) {
                                    echo wp_kses_post($change['new_block']['innerHTML']);
                                } else {
                                    echo '<p>No content available</p>';
                                }
                                
                                echo '</div>';
                                
                                echo '</div>';
                            } else if (isset($change['block'])) {
                                // Handle regulatory blocks with paragraphs
                                if (isset($change['block']['attrs']) && isset($change['block']['attrs']['paragraphs'])) {
                                    foreach ($change['block']['attrs']['paragraphs'] as $para) {
                                        echo '<div style="margin-bottom: 10px;">';
                                        echo '<strong>Paragraph ' . (isset($para['number']) ? esc_html($para['number']) : '') . ':</strong> ';
                                        echo wp_kses_post($para['text']);
                                        echo '</div>';
                                    }
                                } else if (isset($change['block']['innerHTML'])) {
                                    echo wp_kses_post($change['block']['innerHTML']);
                                } else {
                                    echo '<p>No content available</p>';
                                }
                            } else {
                                echo '<p>No block data available</p>';
                            }
                            
                            echo '</div>';
                        }
                        
                        echo '</div>';
                    }
                    
                    echo '</div>';
                }
            }
        } else {
            echo '<p>Changes data is not in the expected format.</p>';
            echo '<pre>';
            print_r(array_keys($changes_data));
            echo '</pre>';
        }
        
        echo '</div>';
    } else {
        echo '<p>No changes data found for this changelog.</p>';
    }
    
    echo '</div>';
    
    return ob_get_clean();
}
add_shortcode('astp_changelog_preview', 'astp_changelog_preview_shortcode'); 
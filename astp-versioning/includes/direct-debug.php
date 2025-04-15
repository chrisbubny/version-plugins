<?php
/**
 * Direct Changelog Debug Tool
 * 
 * This file can be accessed directly to debug changelog data without 
 * having to add a shortcode to a page.
 */

// Bootstrap WordPress
define('WP_USE_THEMES', false);
require_once('../../../../../wp-load.php');

// Set debug mode
if (!defined('WP_DEBUG')) {
    define('WP_DEBUG', true);
}

// Security check
if (!current_user_can('manage_options')) {
    wp_die('You do not have permission to access this page.');
}

// Get input parameters
$post_id = isset($_GET['post_id']) ? intval($_GET['post_id']) : 0;
$changelog_id = isset($_GET['changelog_id']) ? intval($_GET['changelog_id']) : 0;
$doc_type = isset($_GET['doc_type']) ? sanitize_text_field($_GET['doc_type']) : 'ccg';

?><!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Changelog Debug Tool</title>
    <style>
        body { 
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
            line-height: 1.5;
            color: #333;
            margin: 0;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: #fff;
            padding: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        h1, h2, h3, h4 { margin-top: 0; }
        pre {
            background: #f8f8f8;
            padding: 10px;
            overflow: auto;
            border: 1px solid #ddd;
            font-size: 12px;
            max-height: 400px;
        }
        .debug-tabs {
            display: flex;
            border-bottom: 1px solid #ddd;
            margin-bottom: 20px;
        }
        .debug-tab {
            padding: 10px 15px;
            cursor: pointer;
            border: 1px solid transparent;
            margin-bottom: -1px;
        }
        .debug-tab.active {
            border: 1px solid #ddd;
            border-bottom-color: #fff;
            background: #fff;
        }
        .debug-panel {
            display: none;
        }
        .debug-panel.active {
            display: block;
        }
        .toolbar {
            background: #f1f1f1;
            padding: 10px;
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        form {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }
        button, input[type="submit"] {
            padding: 8px 16px;
            background: #0073aa;
            color: white;
            border: none;
            border-radius: 3px;
            cursor: pointer;
        }
        button:hover, input[type="submit"]:hover {
            background: #005c8a;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Changelog Debug Tool</h1>
        
        <div class="toolbar">
            <form method="get">
                <label for="post_id">Post ID:</label>
                <input type="number" id="post_id" name="post_id" value="<?php echo esc_attr($post_id); ?>">
                
                <label for="changelog_id">Changelog ID:</label>
                <input type="number" id="changelog_id" name="changelog_id" value="<?php echo esc_attr($changelog_id); ?>">
                
                <label for="doc_type">Doc Type:</label>
                <select id="doc_type" name="doc_type">
                    <option value="ccg" <?php selected($doc_type, 'ccg'); ?>>CCG</option>
                    <option value="tp" <?php selected($doc_type, 'tp'); ?>>TP</option>
                </select>
                
                <input type="submit" value="Load Data">
            </form>
        </div>
        
        <div class="debug-tabs">
            <div class="debug-tab active" data-tab="versions">Versions</div>
            <div class="debug-tab" data-tab="changelog">Changelog</div>
            <div class="debug-tab" data-tab="preview">Preview</div>
            <div class="debug-tab" data-tab="raw">Raw Data</div>
        </div>
        
        <div class="debug-panel active" id="versions-panel">
            <h2>Version Information</h2>
            <?php
            if ($post_id) {
                // Get versions for CCG
                $ccg_versions = astp_get_version_history('ccg', $post_id);
                // Get versions for TP
                $tp_versions = astp_get_version_history('tp', $post_id);
                
                echo '<h3>CCG Versions (' . count($ccg_versions) . ')</h3>';
                if (!empty($ccg_versions)) {
                    echo '<table style="width: 100%; border-collapse: collapse;">';
                    echo '<tr style="background: #f1f1f1;"><th style="text-align: left; padding: 8px; border: 1px solid #ddd;">Version</th><th style="text-align: left; padding: 8px; border: 1px solid #ddd;">Date</th><th style="text-align: left; padding: 8px; border: 1px solid #ddd;">Version ID</th><th style="text-align: left; padding: 8px; border: 1px solid #ddd;">Changelog ID</th><th style="text-align: left; padding: 8px; border: 1px solid #ddd;">Changes</th><th style="text-align: left; padding: 8px; border: 1px solid #ddd;">Actions</th></tr>';
                    
                    foreach ($ccg_versions as $version) {
                        echo '<tr>';
                        echo '<td style="padding: 8px; border: 1px solid #ddd;">' . esc_html($version['version']) . '</td>';
                        echo '<td style="padding: 8px; border: 1px solid #ddd;">' . esc_html($version['date']) . '</td>';
                        echo '<td style="padding: 8px; border: 1px solid #ddd;">' . esc_html($version['version_id']) . '</td>';
                        echo '<td style="padding: 8px; border: 1px solid #ddd;">' . (isset($version['changelog_id']) ? esc_html($version['changelog_id']) : 'N/A') . '</td>';
                        echo '<td style="padding: 8px; border: 1px solid #ddd;">' . (isset($version['changes']) ? count($version['changes']) : '0') . ' changes</td>';
                        echo '<td style="padding: 8px; border: 1px solid #ddd;">';
                        
                        if (isset($version['changelog_id'])) {
                            $cl_id = $version['changelog_id'];
                            echo '<a href="?post_id=' . esc_attr($post_id) . '&changelog_id=' . esc_attr($cl_id) . '&doc_type=ccg">View Changelog</a>';
                        }
                        
                        echo '</td>';
                        echo '</tr>';
                    }
                    
                    echo '</table>';
                } else {
                    echo '<p>No CCG versions found for this post.</p>';
                }
                
                echo '<h3>TP Versions (' . count($tp_versions) . ')</h3>';
                if (!empty($tp_versions)) {
                    echo '<table style="width: 100%; border-collapse: collapse;">';
                    echo '<tr style="background: #f1f1f1;"><th style="text-align: left; padding: 8px; border: 1px solid #ddd;">Version</th><th style="text-align: left; padding: 8px; border: 1px solid #ddd;">Date</th><th style="text-align: left; padding: 8px; border: 1px solid #ddd;">Version ID</th><th style="text-align: left; padding: 8px; border: 1px solid #ddd;">Changelog ID</th><th style="text-align: left; padding: 8px; border: 1px solid #ddd;">Changes</th><th style="text-align: left; padding: 8px; border: 1px solid #ddd;">Actions</th></tr>';
                    
                    foreach ($tp_versions as $version) {
                        echo '<tr>';
                        echo '<td style="padding: 8px; border: 1px solid #ddd;">' . esc_html($version['version']) . '</td>';
                        echo '<td style="padding: 8px; border: 1px solid #ddd;">' . esc_html($version['date']) . '</td>';
                        echo '<td style="padding: 8px; border: 1px solid #ddd;">' . esc_html($version['version_id']) . '</td>';
                        echo '<td style="padding: 8px; border: 1px solid #ddd;">' . (isset($version['changelog_id']) ? esc_html($version['changelog_id']) : 'N/A') . '</td>';
                        echo '<td style="padding: 8px; border: 1px solid #ddd;">' . (isset($version['changes']) ? count($version['changes']) : '0') . ' changes</td>';
                        echo '<td style="padding: 8px; border: 1px solid #ddd;">';
                        
                        if (isset($version['changelog_id'])) {
                            $cl_id = $version['changelog_id'];
                            echo '<a href="?post_id=' . esc_attr($post_id) . '&changelog_id=' . esc_attr($cl_id) . '&doc_type=tp">View Changelog</a>';
                        }
                        
                        echo '</td>';
                        echo '</tr>';
                    }
                    
                    echo '</table>';
                } else {
                    echo '<p>No TP versions found for this post.</p>';
                }
            } else {
                echo '<p>Enter a Post ID to view versions.</p>';
            }
            ?>
        </div>
        
        <div class="debug-panel" id="changelog-panel">
            <h2>Changelog Details</h2>
            <?php
            if ($changelog_id) {
                echo '<h3>Changelog ID: ' . esc_html($changelog_id) . '</h3>';
                
                // Get the changelog post
                $changelog_post = get_post($changelog_id);
                
                if ($changelog_post) {
                    echo '<h4>Changelog Post</h4>';
                    echo '<table style="width: 100%; border-collapse: collapse;">';
                    echo '<tr><td style="padding: 8px; border: 1px solid #ddd; width: 150px;">Title:</td><td style="padding: 8px; border: 1px solid #ddd;">' . esc_html($changelog_post->post_title) . '</td></tr>';
                    echo '<tr><td style="padding: 8px; border: 1px solid #ddd;">Status:</td><td style="padding: 8px; border: 1px solid #ddd;">' . esc_html($changelog_post->post_status) . '</td></tr>';
                    echo '<tr><td style="padding: 8px; border: 1px solid #ddd;">Type:</td><td style="padding: 8px; border: 1px solid #ddd;">' . esc_html($changelog_post->post_type) . '</td></tr>';
                    echo '<tr><td style="padding: 8px; border: 1px solid #ddd;">Created:</td><td style="padding: 8px; border: 1px solid #ddd;">' . esc_html($changelog_post->post_date) . '</td></tr>';
                    echo '</table>';
                    
                    // Get all meta for the changelog
                    $meta_keys = get_post_custom_keys($changelog_id);
                    
                    if ($meta_keys) {
                        echo '<h4>Changelog Meta Keys</h4>';
                        echo '<ul>';
                        foreach ($meta_keys as $key) {
                            echo '<li>' . esc_html($key) . '</li>';
                        }
                        echo '</ul>';
                        
                        // Check specific meta values
                        $version_id = get_post_meta($changelog_id, 'version_id', true);
                        echo '<p>Associated Version ID: ' . ($version_id ? esc_html($version_id) : 'None') . '</p>';
                        
                        // Get changes_data meta
                        $changes_data = get_post_meta($changelog_id, 'changes_data', true);
                        
                        if (!empty($changes_data)) {
                            echo '<h4>Changes Data Structure</h4>';
                            
                            // Show keys and counts
                            echo '<table style="width: 100%; border-collapse: collapse;">';
                            echo '<tr style="background: #f1f1f1;"><th style="text-align: left; padding: 8px; border: 1px solid #ddd;">Key</th><th style="text-align: left; padding: 8px; border: 1px solid #ddd;">Type</th><th style="text-align: left; padding: 8px; border: 1px solid #ddd;">Count</th></tr>';
                            
                            foreach ($changes_data as $key => $value) {
                                echo '<tr>';
                                echo '<td style="padding: 8px; border: 1px solid #ddd;">' . esc_html($key) . '</td>';
                                echo '<td style="padding: 8px; border: 1px solid #ddd;">' . esc_html(gettype($value)) . '</td>';
                                
                                if (is_array($value)) {
                                    echo '<td style="padding: 8px; border: 1px solid #ddd;">' . count($value) . ' items</td>';
                                } else {
                                    echo '<td style="padding: 8px; border: 1px solid #ddd;">N/A</td>';
                                }
                                
                                echo '</tr>';
                            }
                            
                            echo '</table>';
                            
                            // Show sample data
                            foreach (['added', 'removed', 'amended'] as $change_type) {
                                if (isset($changes_data[$change_type]) && is_array($changes_data[$change_type]) && !empty($changes_data[$change_type])) {
                                    echo '<h4>' . ucfirst($change_type) . ' Changes (' . count($changes_data[$change_type]) . ')</h4>';
                                    
                                    // Show sample
                                    $sample = $changes_data[$change_type][0];
                                    echo '<p>First ' . $change_type . ' change data structure:</p>';
                                    echo '<pre>';
                                    print_r(array_keys($sample));
                                    echo '</pre>';
                                    
                                    // Section details
                                    if (isset($sample['section'])) {
                                        echo '<p>Section data structure:</p>';
                                        echo '<pre>';
                                        print_r($sample['section']);
                                        echo '</pre>';
                                    }
                                    
                                    // Block details
                                    if (isset($sample['block'])) {
                                        echo '<p>Block structure:</p>';
                                        echo '<pre>';
                                        if (isset($sample['block']['attrs']) && isset($sample['block']['attrs']['paragraphs'])) {
                                            echo 'Has paragraphs attribute with ' . count($sample['block']['attrs']['paragraphs']) . ' paragraphs';
                                        }
                                        echo "\n";
                                        if (isset($sample['block']['innerHTML'])) {
                                            echo 'Has innerHTML content: ' . (strlen($sample['block']['innerHTML']) > 100 ? substr($sample['block']['innerHTML'], 0, 100) . '...' : $sample['block']['innerHTML']);
                                        }
                                        echo '</pre>';
                                    }
                                }
                            }
                        } else {
                            echo '<p>No changes_data meta found.</p>';
                        }
                        
                        // Check for block data
                        $block_data = get_post_meta($changelog_id, 'changelog_block_data', true);
                        if (!empty($block_data)) {
                            echo '<h4>Changelog Block Data</h4>';
                            echo '<pre>';
                            print_r(array_keys($block_data));
                            echo '</pre>';
                        }
                    } else {
                        echo '<p>No metadata found for this changelog.</p>';
                    }
                } else {
                    echo '<p>Changelog post not found. It might have been deleted.</p>';
                }
            } else {
                echo '<p>Select a changelog ID to view details.</p>';
            }
            ?>
        </div>
        
        <div class="debug-panel" id="preview-panel">
            <h2>Changelog Preview</h2>
            <?php
            if ($changelog_id) {
                // Get the changes data
                $changes_data = get_post_meta($changelog_id, 'changes_data', true);
                $structured_data = get_post_meta($changelog_id, 'changelog_block_data', true);
                
                echo '<div style="margin-bottom: 20px;">';
                echo '<h3>Changelog ID: ' . esc_html($changelog_id) . '</h3>';
                
                // Try to display structured changelog data first
                if (!empty($structured_data) && is_array($structured_data)) {
                    echo '<div class="changelog-structured-preview">';
                    echo '<h4>Structured Changelog Data</h4>';
                    
                    foreach ($structured_data as $section_key => $section) {
                        echo '<div class="change-section" style="margin-bottom: 20px; border-bottom: 1px solid #ddd; padding-bottom: 15px;">';
                        echo '<h5>' . (isset($section['name']) ? esc_html($section['name']) : esc_html($section_key)) . '</h5>';
                        
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
                    echo '<h4>Raw Changes Data</h4>';
                    
                    // Check if using the standard format with added/removed/amended as top keys
                    $standard_format = isset($changes_data['added']) || isset($changes_data['removed']) || isset($changes_data['amended']);
                    
                    if ($standard_format) {
                        // Process each change type
                        foreach (['added', 'removed', 'amended'] as $change_type) {
                            if (isset($changes_data[$change_type]) && is_array($changes_data[$change_type]) && !empty($changes_data[$change_type])) {
                                echo '<div class="changes-group" style="margin-bottom: 20px;">';
                                echo '<h5 style="color: ';
                                
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
                                
                                echo ';">' . ucfirst($change_type) . ' Changes (' . count($changes_data[$change_type]) . ')</h5>';
                                
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
                                    echo '<h6>' . esc_html($section) . '</h6>';
                                    
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
            } else {
                echo '<p>Select a changelog ID to preview.</p>';
            }
            ?>
        </div>
        
        <div class="debug-panel" id="raw-panel">
            <h2>Raw Data Dump</h2>
            <?php
            if ($changelog_id) {
                $changes_data = get_post_meta($changelog_id, 'changes_data', true);
                $block_data = get_post_meta($changelog_id, 'changelog_block_data', true);
                
                echo '<h3>Changes Data</h3>';
                echo '<pre>';
                print_r($changes_data);
                echo '</pre>';
                
                echo '<h3>Block Data</h3>';
                echo '<pre>';
                print_r($block_data);
                echo '</pre>';

                echo '<h3>Version History Function Output</h3>';
                echo '<p>Direct output from astp_get_version_history():</p>';
                echo '<pre>';
                $version_history = astp_get_version_history($doc_type, $post_id);
                echo esc_html(print_r($version_history, true));
                echo '</pre>';
            } else {
                echo '<p>Select a changelog ID to view raw data.</p>';
            }
            ?>
        </div>
    </div>
    
    <script>
        // Simple tabs
        const tabs = document.querySelectorAll('.debug-tab');
        const panels = document.querySelectorAll('.debug-panel');
        
        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                // Remove active class from all tabs
                tabs.forEach(t => t.classList.remove('active'));
                // Add active class to clicked tab
                tab.classList.add('active');
                
                // Hide all panels
                panels.forEach(p => p.classList.remove('active'));
                // Show corresponding panel
                const panelId = tab.getAttribute('data-tab') + '-panel';
                document.getElementById(panelId).classList.add('active');
            });
        });
    </script>
</body>
</html> 
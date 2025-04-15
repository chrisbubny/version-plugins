<?php
/**
 * Admin UI Functions
 *
 * @package ASTP_Versioning
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Add admin menu items for Test Method and related posts
 */
function astp_add_admin_menu() {
    // Add settings page
    add_submenu_page(
        'edit.php?post_type=astp_test_method',
        __('ASTP Versioning Settings', 'astp-versioning'),
        __('Settings', 'astp-versioning'),
        'manage_options',
        'astp-settings',
        'astp_settings_page'
    );
    
    // Add version comparison tool
    add_submenu_page(
        'edit.php?post_type=astp_test_method',
        __('Version Comparison', 'astp-versioning'),
        __('Compare Versions', 'astp-versioning'),
        'edit_posts',
        'astp-version-comparison',
        'astp_version_comparison_page'
    );
    
    // Add version history page
    add_submenu_page(
        'edit.php?post_type=astp_test_method',
        __('Version History', 'astp-versioning'),
        __('Version History', 'astp-versioning'),
        'edit_posts',
        'astp-version-history',
        'astp_version_history_page'
    );
    
    // Add changelog regeneration tool
    add_submenu_page(
        'edit.php?post_type=astp_test_method',
        __('Regenerate Changelogs', 'astp-versioning'),
        __('Regenerate Changelogs', 'astp-versioning'),
        'edit_posts',
        'astp-regenerate-changelog',
        'astp_regenerate_changelog_page'
    );
}
add_action('admin_menu', 'astp_add_admin_menu');

/**
 * Register additional meta boxes for Test Method post type
 */
function astp_register_meta_boxes() {
    // Version History metabox for Test Method
    add_meta_box(
        'astp_version_history_meta_box',
        __('Version History', 'astp-versioning'),
        'astp_version_history_meta_box_callback',
        'astp_test_method',
        'normal',
        'default'
    );
    
    // Regenerate Changelog metabox for Test Method (added here)
    add_meta_box(
        'astp_regenerate_changelog_meta_box',
        __('Regenerate Changelog', 'astp-versioning'),
        'astp_add_regenerate_changelog_button',
        'astp_test_method',
        'side',
        'default'
    );
    
    // Version details metabox for Version post type
    add_meta_box(
        'astp_version_details_meta_box',
        __('Version Details', 'astp-versioning'),
        'astp_version_details_meta_box_callback',
        'astp_version',
        'normal',
        'high'
    );
    
    // Version changes metabox for Version post type
    add_meta_box(
        'astp_version_changes_meta_box',
        __('Changes', 'astp-versioning'),
        'astp_version_changes_meta_box_callback',
        'astp_version',
        'normal',
        'default'
    );
}
add_action('add_meta_boxes', 'astp_register_meta_boxes');

/**
 * Settings page callback
 */
function astp_settings_page() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        
        <form method="post" action="options.php">
            <?php
            settings_fields('astp_settings');
            do_settings_sections('astp_settings');
            submit_button(__('Save Settings', 'astp-versioning'));
            ?>
        </form>
        
        <hr>
        
        <h2><?php _e('GitHub Integration Test', 'astp-versioning'); ?></h2>
        <p><?php _e('Test your GitHub configuration to ensure everything is set up correctly.', 'astp-versioning'); ?></p>
        <p>
            <button type="button" id="astp-test-github" class="button button-secondary">
                <?php _e('Test GitHub Connection', 'astp-versioning'); ?>
            </button>
            <span id="astp-github-test-result" class="notice" style="display: none; padding: 8px;"></span>
        </p>
    </div>
    <?php
}

/**
 * Version comparison admin page
 */
function astp_version_comparison_page() {
    if (!current_user_can('edit_posts')) {
        return;
    }
    
    // Get from and to version IDs from request
    $from_version_id = isset($_GET['from']) ? intval($_GET['from']) : 0;
    $to_version_id = isset($_GET['to']) ? intval($_GET['to']) : 0;
    
    // Get all versions for selection
    $all_versions = get_posts([
        'post_type' => 'astp_version',
        'posts_per_page' => -1,
        'orderby' => 'meta_value',
        'meta_key' => 'version_number',
        'order' => 'DESC',
    ]);
    
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        
        <div class="astp-version-comparison-selector">
            <form method="get" action="">
                <input type="hidden" name="page" value="astp-version-comparison">
                
                <div class="astp-comparison-controls">
                    <div class="astp-comparison-field">
                        <label for="astp-from-version"><?php _e('From Version:', 'astp-versioning'); ?></label>
                        <select name="from" id="astp-from-version">
                            <option value=""><?php _e('Select a version', 'astp-versioning'); ?></option>
                            <?php foreach ($all_versions as $version) : 
                                $version_number = get_post_meta($version->ID, 'version_number', true);
                                $parent_post = wp_get_post_parent_id($version->ID) ? get_the_title(wp_get_post_parent_id($version->ID)) : '';
                                $selected = $from_version_id == $version->ID ? 'selected' : '';
                            ?>
                                <option value="<?php echo $version->ID; ?>" <?php echo $selected; ?>>
                                    <?php echo esc_html($parent_post . ' - v' . $version_number); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="astp-comparison-field">
                        <label for="astp-to-version"><?php _e('To Version:', 'astp-versioning'); ?></label>
                        <select name="to" id="astp-to-version">
                            <option value=""><?php _e('Select a version', 'astp-versioning'); ?></option>
                            <?php foreach ($all_versions as $version) : 
                                $version_number = get_post_meta($version->ID, 'version_number', true);
                                $parent_post = wp_get_post_parent_id($version->ID) ? get_the_title(wp_get_post_parent_id($version->ID)) : '';
                                $selected = $to_version_id == $version->ID ? 'selected' : '';
                            ?>
                                <option value="<?php echo $version->ID; ?>" <?php echo $selected; ?>>
                                    <?php echo esc_html($parent_post . ' - v' . $version_number); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="astp-comparison-field">
                        <label for="astp-comparison-format"><?php _e('Display Format:', 'astp-versioning'); ?></label>
                        <select name="format" id="astp-comparison-format">
                            <option value="summary"><?php _e('Summary', 'astp-versioning'); ?></option>
                            <option value="detailed" selected><?php _e('Detailed', 'astp-versioning'); ?></option>
                            <option value="full"><?php _e('Full', 'astp-versioning'); ?></option>
                        </select>
                    </div>
                    
                    <div class="astp-comparison-buttons">
                        <button type="submit" class="button button-primary"><?php _e('Compare Versions', 'astp-versioning'); ?></button>
                    </div>
                </div>
            </form>
        </div>
        
        <?php if ($from_version_id && $to_version_id) : ?>
        <div class="astp-comparison-results">
            <h2><?php _e('Comparison Results', 'astp-versioning'); ?></h2>
            
            <?php
            // Get version information
            $from_version_number = get_post_meta($from_version_id, 'version_number', true);
            $to_version_number = get_post_meta($to_version_id, 'version_number', true);
            $format = isset($_GET['format']) ? sanitize_text_field($_GET['format']) : 'detailed';
            
            // Display comparison header
            echo '<div class="astp-comparison-header">';
            echo '<p>' . sprintf(
                __('Comparing version %s to version %s', 'astp-versioning'),
                '<strong>' . esc_html($from_version_number) . '</strong>',
                '<strong>' . esc_html($to_version_number) . '</strong>'
            ) . '</p>';
            echo '</div>';
            
            // Get changes and render
            $changes = astp_get_version_changes($from_version_id, $to_version_id);
            echo astp_render_changes_html($changes, $format);
            ?>
        </div>
        <?php endif; ?>
    </div>
    <?php
}

/**
 * Version history metabox callback for Test Method
 *
 * @param WP_Post $post The current post object
 */
function astp_version_history_meta_box_callback($post, $document_type = '') {
    // Get all versions for this post
    if (!empty($document_type) && in_array($document_type, ['ccg', 'tp'])) {
        // Get document-specific versions if requested
        $versions_meta_key = $document_type . '_versions_history';
        $version_ids = get_post_meta($post->ID, $versions_meta_key, true) ?: [];
        
        if (!empty($version_ids)) {
            $versions = [];
            foreach ($version_ids as $version_id) {
                $version = get_post($version_id);
                if ($version && $version->post_type === 'astp_version') {
                    $versions[] = $version;
                }
            }
        } else {
            $versions = [];
        }
    } else {
        // Get all versions
    $versions = astp_get_content_versions($post->ID);
    }
    
    if (empty($versions)) {
        if (!empty($document_type)) {
            echo '<p>' . sprintf(__('No %s versions found for this test method.', 'astp-versioning'), 
                $document_type === 'ccg' ? 'Certification Companion Guide' : 'Test Procedure') . '</p>';
        } else {
        echo '<p>' . __('No versions found for this test method.', 'astp-versioning') . '</p>';
        }
        return;
    }
    
    echo '<div class="astp-admin-version-history">';
    echo '<table class="widefat">';
    echo '<thead>';
    echo '<tr>';
    echo '<th>' . __('Version', 'astp-versioning') . '</th>';
    echo '<th>' . __('Date', 'astp-versioning') . '</th>';
    echo '<th>' . __('Type', 'astp-versioning') . '</th>';
    echo '<th>' . __('Status', 'astp-versioning') . '</th>';
    echo '<th>' . __('Actions', 'astp-versioning') . '</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';
    
    foreach ($versions as $version) {
        // Get version metadata
        $version_number = get_post_meta($version->ID, 'version_number', true);
        $release_date = get_post_meta($version->ID, 'release_date', true);
        $formatted_date = date_i18n(get_option('date_format'), strtotime($release_date));
        
        // Get version type
        $version_type_terms = wp_get_object_terms($version->ID, 'version_type', ['fields' => 'names']);
        $version_type = !empty($version_type_terms) ? $version_type_terms[0] : '';
        
        // Get version status
        $version_status_terms = wp_get_object_terms($version->ID, 'version_status', ['fields' => 'names']);
        $version_status = !empty($version_status_terms) ? $version_status_terms[0] : '';
        
        // Get document type for this version if known
        $version_document_type = get_post_meta($version->ID, 'document_type', true);
        
        // Add CSS class for document type if available
        $row_class = '';
        if (!empty($version_document_type)) {
            $row_class = ' class="astp-' . esc_attr($version_document_type) . '-row"';
        }
        
        echo '<tr' . $row_class . '>';
        echo '<td><strong>' . esc_html($version_number) . '</strong></td>';
        echo '<td>' . esc_html($formatted_date) . '</td>';
        echo '<td>' . esc_html($version_type) . '</td>';
        echo '<td>' . esc_html($version_status) . '</td>';
        echo '<td>';
        
        // Actions
        echo '<a href="' . get_edit_post_link($version->ID) . '" class="button button-small">' . __('View', 'astp-versioning') . '</a> ';
        
        // If this is the current version, show create-from options
        $is_current = false;
        if (!empty($version_document_type)) {
            // Check document-specific current version
            $current_doc_version_id = get_post_meta($post->ID, $version_document_type . '_current_published_version', true);
            $is_current = ($version->ID === intval($current_doc_version_id));
        } else {
            // Check legacy current version
        $current_version_id = get_post_meta($post->ID, 'current_published_version', true);
            $is_current = ($version->ID === intval($current_version_id));
        }
        
        if ($is_current) {
            $doc_type_attr = !empty($version_document_type) ? ' data-document-type="' . esc_attr($version_document_type) . '"' : '';
            echo '<a href="#" class="button button-small astp-create-version" data-post-id="' . $post->ID . '"' . $doc_type_attr . '>' . __('New Version', 'astp-versioning') . '</a> ';
            echo '<a href="#" class="button button-small astp-create-hotfix" data-version-id="' . $version->ID . '" data-post-id="' . $post->ID . '"' . $doc_type_attr . '>' . __('Hotfix', 'astp-versioning') . '</a> ';
        }
        
        // If this is an in-development version, show publish option
        $is_in_dev = false;
        if (!empty($version_document_type)) {
            // Check document-specific in-dev version
            $in_dev_doc_version_id = get_post_meta($post->ID, $version_document_type . '_in_development_version', true);
            $is_in_dev = ($version->ID === intval($in_dev_doc_version_id));
        } else {
            // Check legacy in-dev version
        $in_dev_version_id = get_post_meta($post->ID, 'in_development_version', true);
            $is_in_dev = ($version->ID === intval($in_dev_version_id));
        }
        
        if ($is_in_dev) {
            $doc_type_attr = !empty($version_document_type) ? ' data-document-type="' . esc_attr($version_document_type) . '"' : '';
            echo '<a href="#" class="button button-small button-primary astp-publish-version" data-version-id="' . $version->ID . '" data-post-id="' . $post->ID . '"' . $doc_type_attr . '>' . __('Publish', 'astp-versioning') . '</a> ';
        }
        
        echo '</td>';
        echo '</tr>';
    }
    
    echo '</tbody>';
    echo '</table>';
    
    // Add CSS for document type rows
    echo '<style>
        .astp-ccg-row {
            background-color: #f0f8ff !important;
        }
        .astp-tp-row {
            background-color: #f0fff0 !important;
        }
    </style>';
    
    echo '</div>';
    
    // Add nonce for AJAX actions
    wp_nonce_field('astp_version_actions', 'astp_version_nonce');
}

/**
 * Version details metabox callback for Version post type
 *
 * @param WP_Post $post The current post object
 */
function astp_version_details_meta_box_callback($post) {
    // Get version metadata
    $version_number = get_post_meta($post->ID, 'version_number', true);
    $release_date = get_post_meta($post->ID, 'release_date', true);
    $parent_id = wp_get_post_parent_id($post->ID);
    $previous_version_id = get_post_meta($post->ID, 'previous_version_id', true);
    $is_hotfix = get_post_meta($post->ID, 'is_hotfix_for', true);
    $is_rollback = get_post_meta($post->ID, 'is_rollback', true);
    
    echo '<div class="astp-version-details">';
    
    echo '<div class="astp-detail-row">';
    echo '<label>' . __('Version Number:', 'astp-versioning') . '</label>';
    echo '<div class="astp-detail-value"><strong>' . esc_html($version_number) . '</strong></div>';
    echo '</div>';
    
    echo '<div class="astp-detail-row">';
    echo '<label>' . __('Release Date:', 'astp-versioning') . '</label>';
    echo '<div class="astp-detail-value">' . esc_html(date_i18n(get_option('date_format'), strtotime($release_date))) . '</div>';
    echo '</div>';
    
    echo '<div class="astp-detail-row">';
    echo '<label>' . __('Parent Test Method:', 'astp-versioning') . '</label>';
    echo '<div class="astp-detail-value"><a href="' . get_edit_post_link($parent_id) . '">' . get_the_title($parent_id) . '</a></div>';
    echo '</div>';
    
    // Version Type
    $version_type_terms = wp_get_object_terms($post->ID, 'version_type', ['fields' => 'names']);
    $version_type = !empty($version_type_terms) ? $version_type_terms[0] : '';
    
    echo '<div class="astp-detail-row">';
    echo '<label>' . __('Version Type:', 'astp-versioning') . '</label>';
    echo '<div class="astp-detail-value">' . esc_html($version_type) . '</div>';
    echo '</div>';
    
    // Version Status
    $version_status_terms = wp_get_object_terms($post->ID, 'version_status', ['fields' => 'names']);
    $version_status = !empty($version_status_terms) ? $version_status_terms[0] : '';
    
    echo '<div class="astp-detail-row">';
    echo '<label>' . __('Status:', 'astp-versioning') . '</label>';
    echo '<div class="astp-detail-value">';
    if ($version_status === 'Published') {
        echo '<span class="astp-status astp-status-published">' . esc_html($version_status) . '</span>';
    } elseif ($version_status === 'In Development') {
        echo '<span class="astp-status astp-status-development">' . esc_html($version_status) . '</span>';
    } else {
        echo esc_html($version_status);
    }
    echo '</div>';
    echo '</div>';
    
    // Previous Version
    if ($previous_version_id) {
        $prev_version_number = get_post_meta($previous_version_id, 'version_number', true);
        
        echo '<div class="astp-detail-row">';
        echo '<label>' . __('Previous Version:', 'astp-versioning') . '</label>';
        echo '<div class="astp-detail-value"><a href="' . get_edit_post_link($previous_version_id) . '">v' . esc_html($prev_version_number) . '</a></div>';
        echo '</div>';
    }
    
    // Special version types
    if ($is_hotfix) {
        $hotfix_for_version = get_post($is_hotfix);
        $hotfix_for_number = get_post_meta($is_hotfix, 'version_number', true);
        
        echo '<div class="astp-detail-row">';
        echo '<label>' . __('Hotfix For:', 'astp-versioning') . '</label>';
        echo '<div class="astp-detail-value"><a href="' . get_edit_post_link($is_hotfix) . '">v' . esc_html($hotfix_for_number) . '</a></div>';
        echo '</div>';
    }
    
    if ($is_rollback) {
        $rollback_source = get_post_meta($post->ID, 'rollback_source', true);
        $rollback_source_number = get_post_meta($rollback_source, 'version_number', true);
        
        echo '<div class="astp-detail-row">';
        echo '<label>' . __('Rollback To:', 'astp-versioning') . '</label>';
        echo '<div class="astp-detail-value"><a href="' . get_edit_post_link($rollback_source) . '">v' . esc_html($rollback_source_number) . '</a></div>';
        echo '</div>';
    }
    
    // GitHub URL if published
    $github_url = get_post_meta($post->ID, 'github_url', true);
    if ($github_url) {
        echo '<div class="astp-detail-row">';
        echo '<label>' . __('GitHub:', 'astp-versioning') . '</label>';
        echo '<div class="astp-detail-value"><a href="' . esc_url($github_url) . '" target="_blank">' . __('View on GitHub', 'astp-versioning') . '</a></div>';
        echo '</div>';
    }
    
    echo '</div>'; // .astp-version-details
    
    // Actions
    echo '<div class="astp-version-actions">';
    echo '<hr>';
    
    // Documents section
    echo '<h3>' . __('Documents', 'astp-versioning') . '</h3>';
    
    $pdf_url = get_post_meta($post->ID, 'pdf_document_url', true);
    $word_url = get_post_meta($post->ID, 'word_document_url', true);
    
    if ($pdf_url || $word_url) {
        echo '<div class="astp-document-links">';
        
        if ($pdf_url) {
            echo '<a href="' . esc_url($pdf_url) . '" class="button" target="_blank">' . __('View PDF', 'astp-versioning') . '</a> ';
        }
        
        if ($word_url) {
            echo '<a href="' . esc_url($word_url) . '" class="button" target="_blank">' . __('View Word Document', 'astp-versioning') . '</a> ';
        }
        
        echo '<a href="' . esc_url(astp_get_regenerate_documents_url($post->ID)) . '" class="button button-secondary">' . __('Regenerate Documents', 'astp-versioning') . '</a>';
        
        echo '</div>';
    } else {
        echo '<p>' . __('No documents have been generated for this version yet.', 'astp-versioning') . '</p>';
        echo '<a href="' . esc_url(astp_get_regenerate_documents_url($post->ID)) . '" class="button button-primary">' . __('Generate Documents', 'astp-versioning') . '</a>';
    }
    
    echo '</div>'; // .astp-version-actions
}

/**
 * Version changes metabox callback for Version post type
 *
 * @param WP_Post $post The current post object
 */
function astp_version_changes_meta_box_callback($post) {
    // Get previous version
    $previous_version_id = get_post_meta($post->ID, 'previous_version_id', true);
    
    if (!$previous_version_id) {
        echo '<p>' . __('This is the first version, so there are no changes to display.', 'astp-versioning') . '</p>';
        return;
    }
    
    // Display changes
    astp_display_version_changes_admin($post->ID);
}

/**
 * Display version changes in the admin Edit Version page
 */
function astp_display_version_changes_admin($version_id) {
    // Get the changelog for this version
    $changelog = astp_get_changelog_for_version($version_id);
    
    if (!$changelog) {
        echo '<div class="astp-no-changes notice notice-info inline"><p>No changes detected in this version.</p></div>';
        return;
    }
    
    $changes_data = get_post_meta($changelog->ID, 'changes_data', true);
    
    if (empty($changes_data) || !is_array($changes_data)) {
        echo '<div class="astp-no-changes notice notice-info inline"><p>No detailed changes available for this version.</p></div>';
        return;
    }
    
    // Count changes by type
    $change_counts = array(
        'added' => 0,
        'removed' => 0,
        'amended' => 0
    );
    
    foreach ($changes_data as $section => $section_data) {
        if (!empty($section_data['changes'])) {
            foreach ($section_data['changes'] as $type => $changes) {
                if (!empty($changes)) {
                    $change_counts[$type] += count($changes);
                }
            }
        }
    }
    
    // Display change summary
    echo '<div class="astp-changes-summary">';
    
    echo '<div class="astp-changes-counts">';
    if ($change_counts['added'] > 0) {
        echo '<span class="astp-changes-badge astp-badge-added">' . $change_counts['added'] . ' Added</span>';
    }
    
    if ($change_counts['removed'] > 0) {
        echo '<span class="astp-changes-badge astp-badge-removed">' . $change_counts['removed'] . ' Removed</span>';
    }
    
    if ($change_counts['amended'] > 0) {
        echo '<span class="astp-changes-badge astp-badge-amended">' . $change_counts['amended'] . ' Amended</span>';
    }
    echo '</div>'; // .astp-changes-counts
    
    echo '</div>'; // .astp-changes-summary
    
    // Display each section with changes
    echo '<div class="astp-changes-sections">';
    
    foreach ($changes_data as $section => $section_data) {
        if (empty($section_data['changes'])) {
            continue;
        }
        
        $has_changes = false;
        foreach ($section_data['changes'] as $type => $changes) {
            if (!empty($changes)) {
                $has_changes = true;
                break;
            }
        }
        
        if (!$has_changes) {
            continue;
        }
        
        echo '<div class="astp-admin-section">';
        
        // Section header with reference and title
        if (!empty($section) && $section !== 'undefined') {
            echo '<h3 class="astp-section-header">';
            
            // Extract reference and title if they exist in the section key
            $parts = explode('|', $section);
            if (count($parts) >= 2) {
                echo '<span class="astp-paragraph-reference">' . esc_html($parts[0]) . '</span> ';
                echo '<span class="astp-section-title">' . esc_html($parts[1]) . '</span>';
            } else {
                echo esc_html($section);
            }
            
            echo '</h3>';
        } else {
            echo '<h3 class="astp-section-header">General Changes</h3>';
        }
        
        // Display each type of changes in this section
        $change_types = array('added', 'removed', 'amended');
        
        foreach ($change_types as $type) {
            if (empty($section_data['changes'][$type])) {
                continue;
            }
            
            $changes = $section_data['changes'][$type];
            
            echo '<div class="astp-change-group astp-change-group-' . $type . '">';
            
            // Display heading based on change type
            switch ($type) {
                case 'added':
                    echo '<h4 class="astp-change-type-header"><span class="dashicons dashicons-plus-alt"></span> Added Content</h4>';
                    break;
                case 'removed':
                    echo '<h4 class="astp-change-type-header"><span class="dashicons dashicons-trash"></span> Removed Content</h4>';
                    break;
                case 'amended':
                    echo '<h4 class="astp-change-type-header"><span class="dashicons dashicons-edit"></span> Amended Content</h4>';
                    break;
            }
            
            echo '<ul class="astp-admin-changes-list">';
            
            foreach ($changes as $change) {
                echo '<li class="astp-admin-change-item astp-change-' . $type . '">';
                
                // Display reference and title if available
                if (!empty($change['reference']) || !empty($change['title'])) {
                    echo '<div class="astp-change-header">';
                    
                    if (!empty($change['reference'])) {
                        echo '<strong class="astp-change-reference">' . esc_html($change['reference']) . '</strong> ';
                    }
                    
                    if (!empty($change['title'])) {
                        echo '<span class="astp-change-title">' . esc_html($change['title']) . '</span>';
                    }
                    
                echo '</div>';
            }
            
                // Display content based on change type
                if ($type === 'added' || $type === 'removed') {
                    if (!empty($change['content'])) {
                        echo '<div class="astp-change-content">' . wp_kses_post($change['content']) . '</div>';
                    } elseif (!empty($change['html'])) {
                        echo '<div class="astp-change-content">' . wp_kses_post($change['html']) . '</div>';
                    } elseif (!empty($change['block']) && !empty($change['block']['innerHTML'])) {
                        echo '<div class="astp-change-content">' . wp_kses_post($change['block']['innerHTML']) . '</div>';
                    } else {
                        echo '<div class="astp-change-content"><p>• Content was ' . ($type === 'added' ? 'added' : 'removed') . '</p></div>';
                    }
                } else { // amended
                    // Check if we have diff data
                    if (!empty($change['diff'])) {
                        echo '<div class="astp-change-diff">' . wp_kses_post($change['diff']) . '</div>';
                    } elseif (!empty($change['old_content']) && !empty($change['new_content'])) {
                        // Manual diff display
                        echo '<div class="astp-manual-diff">';
                        echo '<div class="diff-old"><strong>Previous:</strong><br>' . wp_kses_post($change['old_content']) . '</div>';
                        echo '<div class="diff-new"><strong>Updated:</strong><br>' . wp_kses_post($change['new_content']) . '</div>';
            echo '</div>';
                    } elseif (!empty($change['new_content'])) {
                        echo '<div class="astp-new-content">' . wp_kses_post($change['new_content']) . '</div>';
                    } elseif (!empty($change['content'])) {
                        echo '<div class="astp-change-content">' . wp_kses_post($change['content']) . '</div>';
                    } elseif (!empty($change['html'])) {
                        echo '<div class="astp-change-content">' . wp_kses_post($change['html']) . '</div>';
                    } else {
                        echo '<div class="astp-change-content"><p>• Content was updated</p></div>';
                    }
                }
                
                echo '</li>';
            }
            
            echo '</ul>';
            echo '</div>'; // .astp-change-group
        }
        
        echo '</div>'; // .astp-admin-section
    }
    
    echo '</div>'; // .astp-changes-sections
}

/**
 * AJAX handler for creating a new version
 */
function astp_ajax_create_version() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'astp_version_actions')) {
        wp_send_json_error(['message' => 'Invalid security token']);
    }
    
    // Check for post_id and version_type
    if (!isset($_POST['post_id']) || !isset($_POST['version_type'])) {
        wp_send_json_error(['message' => 'Missing parameters']);
    }
    
    $post_id = intval($_POST['post_id']);
    $version_type = sanitize_text_field($_POST['version_type']);
    $document_type = isset($_POST['document_type']) ? sanitize_text_field($_POST['document_type']) : null;
    
    // Validate the post exists and is the correct type
    $post = get_post($post_id);
    if (!$post || $post->post_type !== 'astp_test_method') {
        wp_send_json_error(['message' => 'Invalid post']);
    }
    
    // Create the new version
    $version_id = astp_create_new_version($post_id, $version_type, $document_type);
    
    if ($version_id) {
        wp_send_json_success([
            'version_id' => $version_id,
            'message' => 'Version created successfully',
            'document_type' => $document_type
        ]);
    } else {
        wp_send_json_error(['message' => 'Failed to create version']);
    }
    
    wp_die();
}
add_action('wp_ajax_astp_create_version', 'astp_ajax_create_version');
add_action('wp_ajax_astp_create_new_version', 'astp_ajax_create_version');

/**
 * AJAX handler for creating a hotfix
 */
function astp_ajax_create_hotfix() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'astp_version_actions')) {
        wp_send_json_error(['message' => __('Security check failed', 'astp-versioning')]);
        return;
    }
    
    // Check permissions and parameters
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    $version_id = isset($_POST['version_id']) ? intval($_POST['version_id']) : 0;
    
    if (!$version_id) {
        wp_send_json_error(['message' => __('Missing version ID', 'astp-versioning')]);
        return;
    }
    
    if (!current_user_can('edit_post', $version_id)) {
        wp_send_json_error(['message' => __('Permission denied', 'astp-versioning')]);
        return;
    }
    
    // Create hotfix
    $hotfix_id = astp_create_hotfix_version($version_id);
    
    if ($hotfix_id) {
        wp_send_json_success([
            'message' => __('Hotfix created successfully', 'astp-versioning'),
            'version_id' => $hotfix_id,
            'version_edit_url' => get_edit_post_link($hotfix_id, 'raw'),
        ]);
    } else {
        wp_send_json_error(['message' => __('Failed to create hotfix', 'astp-versioning')]);
    }
}
add_action('wp_ajax_astp_create_hotfix', 'astp_ajax_create_hotfix');

/**
 * AJAX handler for version publication
 */
function astp_ajax_publish_version() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'astp_version_actions')) {
        wp_send_json_error(['message' => 'Invalid security token']);
    }
    
    // Check parameters
    if (!isset($_POST['post_id']) || !isset($_POST['version_id'])) {
        wp_send_json_error(['message' => 'Missing parameters']);
    }
    
    // Get parameters
    $post_id = intval($_POST['post_id']);
    $version_id = intval($_POST['version_id']);
    $document_type = isset($_POST['document_type']) ? sanitize_text_field($_POST['document_type']) : null;
        
        // Publish the version
    $result = astp_publish_version($post_id, $version_id, $document_type);
        
        if ($result) {
            wp_send_json_success([
            'message' => 'Version published successfully',
            'document_type' => $document_type
            ]);
        } else {
        wp_send_json_error(['message' => 'Failed to publish version']);
    }
    
    wp_die();
}
add_action('wp_ajax_astp_publish_version', 'astp_ajax_publish_version');

/**
 * Enqueue admin scripts and styles
 */
function astp_enqueue_admin_scripts() {
    $screen = get_current_screen();
    
    // Only load on our post types
    if (!$screen || !in_array($screen->id, ['astp_test_method', 'astp_version', 'astp_changelog'])) {
        return;
    }
    
    // Enqueue styles
    wp_enqueue_style(
        'astp-admin-styles',
        ASTP_VERSIONING_URL . 'assets/css/admin-ui.css',
        [],
        ASTP_VERSIONING_VERSION
    );
    
    // Enqueue scripts
    wp_enqueue_script(
        'astp-admin-scripts',
        ASTP_VERSIONING_URL . 'assets/js/admin-ui.js',
        ['jquery'],
        ASTP_VERSIONING_VERSION,
        true
    );
    
    // Localize script data
    wp_localize_script('astp-admin-scripts', 'astp_admin', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'admin_url' => admin_url(),
        'nonce' => wp_create_nonce('astp_version_actions'),
        'strings' => [
            'confirmCreate' => __('Are you sure you want to create a new version?', 'astp-versioning'),
            'confirmHotfix' => __('Are you sure you want to create a hotfix?', 'astp-versioning'),
            'confirmPublish' => __('Are you sure you want to publish this version? This will generate documents and push to GitHub if configured.', 'astp-versioning'),
            'confirmGenerate' => __('Are you sure you want to generate this document?', 'astp-versioning'),
            'confirmRegenerate' => __('Are you sure you want to regenerate the documents for this version?', 'astp-versioning'),
            'creating' => __('Creating...', 'astp-versioning'),
            'publishing' => __('Publishing...', 'astp-versioning'),
            'generating' => __('Generating...', 'astp-versioning'),
            'regenerating' => __('Regenerating...', 'astp-versioning'),
            'saving' => __('Saving...', 'astp-versioning'),
            'serverError' => __('Error connecting to server. Please try again.', 'astp-versioning'),
            'selectBothVersions' => __('Please select both versions to compare.', 'astp-versioning'),
            'loadingComparison' => __('Loading comparison...', 'astp-versioning'),
            'documentGenerated' => __('Document generated successfully!', 'astp-versioning'),
            'documentsRegenerated' => __('Documents regenerated successfully!', 'astp-versioning'),
            'downloadNow' => __('Would you like to download the document now?', 'astp-versioning'),
            'settingsSaved' => __('Settings saved successfully!', 'astp-versioning'),
            'error' => __('An error occurred. Please try again.', 'astp-versioning'),
        ],
    ]);
}
add_action('admin_enqueue_scripts', 'astp_enqueue_admin_scripts');

// Add the version history page callback
function astp_version_history_page() {
    if (!current_user_can('edit_posts')) {
        wp_die(__('You do not have sufficient permissions to access this page.', 'astp-versioning'));
    }
    
    $post_id = isset($_GET['post_id']) ? intval($_GET['post_id']) : 0;
    
    if (!$post_id) {
        wp_die(__('No test method specified.', 'astp-versioning'));
    }
    
    $post = get_post($post_id);
    
    if (!$post || $post->post_type !== 'astp_test_method') {
        wp_die(__('Invalid test method.', 'astp-versioning'));
    }
    
    // Get document type if specified
    $document_type = isset($_GET['document_type']) ? sanitize_text_field($_GET['document_type']) : '';
    
    ?>
    <div class="wrap">
        <h1><?php echo __('Version History:', 'astp-versioning') . ' ' . get_the_title($post_id); ?></h1>
        
        <div class="astp-version-history-page">
            <?php
            if (!empty($document_type)) {
                // If a document type is specified, show only that type
            echo '<div id="astp-version-history">';
                
                if ($document_type === 'ccg') {
                    echo '<h2 class="astp-ccg-title">' . __('Certification Companion Guide', 'astp-versioning') . '</h2>';
                } else if ($document_type === 'tp') {
                    echo '<h2 class="astp-tp-title">' . __('Test Procedure', 'astp-versioning') . '</h2>';
                }
                
                astp_version_history_meta_box_callback($post, $document_type);
            echo '</div>';
            } else {
                // If no document type is specified, show both types in separate sections
                // CCG section
                echo '<div id="astp-ccg-version-history" class="astp-document-history-section">';
                echo '<h2 class="astp-ccg-title">' . __('Certification Companion Guide', 'astp-versioning') . '</h2>';
                astp_version_history_meta_box_callback($post, 'ccg');
                echo '</div>';
                
                // TP section
                echo '<div id="astp-tp-version-history" class="astp-document-history-section">';
                echo '<h2 class="astp-tp-title">' . __('Test Procedure', 'astp-versioning') . '</h2>';
                astp_version_history_meta_box_callback($post, 'tp');
                echo '</div>';
                
                // Legacy section if needed
                $has_legacy_versions = false;
                $legacy_versions = get_post_meta($post->ID, 'versions_history', true);
                if ($legacy_versions && is_array($legacy_versions) && !empty($legacy_versions)) {
                    $has_legacy_versions = true;
                }
                
                if ($has_legacy_versions) {
                    echo '<div id="astp-legacy-version-history" class="astp-document-history-section">';
                    echo '<h2 class="astp-legacy-title">' . __('Legacy Versions', 'astp-versioning') . '</h2>';
                    echo '<p class="description">' . __('These versions were created before document type separation.', 'astp-versioning') . '</p>';
                    astp_version_history_meta_box_callback($post, 'legacy');
                    echo '</div>';
                }
            }
            ?>
            
            <p>
                <a href="<?php echo get_edit_post_link($post_id); ?>" class="button">
                    <?php _e('Back to Test Method', 'astp-versioning'); ?>
                </a>
            </p>
        </div>
    </div>
    <?php
}

/**
 * Render version management metabox
 */
function astp_render_version_manager_metabox($post) {
    wp_nonce_field('astp_version_actions', 'astp_version_nonce');
    
    // Get version info
    $current_version_id = get_post_meta($post->ID, 'current_published_version', true);
    $dev_version_id = get_post_meta($post->ID, 'in_development_version', true);
    
    // Get CCG specific versions
    $ccg_current_version_id = get_post_meta($post->ID, 'ccg_current_published_version', true);
    $ccg_dev_version_id = get_post_meta($post->ID, 'ccg_in_development_version', true);
    
    // Get TP specific versions
    $tp_current_version_id = get_post_meta($post->ID, 'tp_current_published_version', true);
    $tp_dev_version_id = get_post_meta($post->ID, 'tp_in_development_version', true);
    
    echo '<div class="astp-version-controls">';
    
    // CCG version section
    echo '<div class="astp-version-section astp-ccg-section">';
    echo '<h3>' . __('Certification Companion Guide (CCG)', 'astp-versioning') . '</h3>';
    
    // CCG Published version section
    echo '<div class="astp-published-version">';
    echo '<h4>' . __('Published Version', 'astp-versioning') . '</h4>';
    
    if ($ccg_current_version_id) {
        $version_number = get_post_meta($ccg_current_version_id, 'version_number', true);
        $version_date = get_post_meta($ccg_current_version_id, 'release_date', true);
        
        echo '<p><strong>' . __('Current:', 'astp-versioning') . '</strong> <span class="astp-version-status astp-status-published">' . esc_html($version_number) . '</span></p>';
        if ($version_date) {
            echo '<p><strong>' . __('Released:', 'astp-versioning') . '</strong> ' . date_i18n(get_option('date_format'), strtotime($version_date)) . '</p>';
        }
        echo '<p><a href="#" class="button astp-view-version" data-version-id="' . esc_attr($ccg_current_version_id) . '">' . __('View Details', 'astp-versioning') . '</a></p>';
        echo '<p><a href="#" class="button astp-create-hotfix" data-version-id="' . esc_attr($ccg_current_version_id) . '" data-post-id="' . esc_attr($post->ID) . '" data-document-type="ccg">' . __('Create Hotfix', 'astp-versioning') . '</a></p>';
    } else {
        echo '<p><em>' . __('No published version yet.', 'astp-versioning') . '</em></p>';
    }
    
    echo '</div>'; // End published version
    
    // CCG Development version section
    echo '<div class="astp-development-version">';
    echo '<h4>' . __('Development Version', 'astp-versioning') . '</h4>';
    
    if ($ccg_dev_version_id) {
        $version_number = get_post_meta($ccg_dev_version_id, 'version_number', true);
        
        echo '<p><strong>' . __('In Progress:', 'astp-versioning') . '</strong> <span class="astp-version-status astp-status-development">' . esc_html($version_number) . '</span></p>';
        echo '<p><a href="#" class="button astp-view-version" data-version-id="' . esc_attr($ccg_dev_version_id) . '">' . __('Edit Version', 'astp-versioning') . '</a></p>';
        echo '<p><a href="#" class="button button-primary astp-publish-version" data-version-id="' . esc_attr($ccg_dev_version_id) . '" data-post-id="' . esc_attr($post->ID) . '" data-document-type="ccg">' . __('Publish Version', 'astp-versioning') . '</a></p>';
    } else {
        echo '<p><em>' . __('No version in development.', 'astp-versioning') . '</em></p>';
        
        // Version creation controls
        echo '<hr>';
        echo '<h4>' . __('Create New Version', 'astp-versioning') . '</h4>';
        echo '<p>' . __('Select version type:', 'astp-versioning') . '</p>';
        echo '<select id="astp-ccg-version-type" class="astp-version-type">
            <option value="minor">' . __('Minor Update', 'astp-versioning') . '</option>
            <option value="major">' . __('Major Release', 'astp-versioning') . '</option>
        </select>';
        echo '<p><a href="#" class="button button-primary astp-create-version" data-post-id="' . esc_attr($post->ID) . '" data-document-type="ccg">' . __('Create New Version', 'astp-versioning') . '</a></p>';
    }
    
    echo '</div>'; // End development version
    
    // CCG Version history link
    echo '<hr>';
    echo '<p><a href="#" class="button astp-view-version-history" data-post-id="' . esc_attr($post->ID) . '" data-document-type="ccg">' . __('View CCG Version History', 'astp-versioning') . '</a></p>';
    
    echo '</div>'; // End CCG section
    
    echo '<hr class="astp-section-divider">';
    
    // TP version section
    echo '<div class="astp-version-section astp-tp-section">';
    echo '<h3>' . __('Test Procedure (TP)', 'astp-versioning') . '</h3>';
    
    // TP Published version section
    echo '<div class="astp-published-version">';
    echo '<h4>' . __('Published Version', 'astp-versioning') . '</h4>';
    
    if ($tp_current_version_id) {
        $version_number = get_post_meta($tp_current_version_id, 'version_number', true);
        $version_date = get_post_meta($tp_current_version_id, 'release_date', true);
        
        echo '<p><strong>' . __('Current:', 'astp-versioning') . '</strong> <span class="astp-version-status astp-status-published">' . esc_html($version_number) . '</span></p>';
        if ($version_date) {
            echo '<p><strong>' . __('Released:', 'astp-versioning') . '</strong> ' . date_i18n(get_option('date_format'), strtotime($version_date)) . '</p>';
        }
        echo '<p><a href="#" class="button astp-view-version" data-version-id="' . esc_attr($tp_current_version_id) . '">' . __('View Details', 'astp-versioning') . '</a></p>';
        echo '<p><a href="#" class="button astp-create-hotfix" data-version-id="' . esc_attr($tp_current_version_id) . '" data-post-id="' . esc_attr($post->ID) . '" data-document-type="tp">' . __('Create Hotfix', 'astp-versioning') . '</a></p>';
    } else {
        echo '<p><em>' . __('No published version yet.', 'astp-versioning') . '</em></p>';
    }
    
    echo '</div>'; // End published version
    
    // TP Development version section
    echo '<div class="astp-development-version">';
    echo '<h4>' . __('Development Version', 'astp-versioning') . '</h4>';
    
    if ($tp_dev_version_id) {
        $version_number = get_post_meta($tp_dev_version_id, 'version_number', true);
        
        echo '<p><strong>' . __('In Progress:', 'astp-versioning') . '</strong> <span class="astp-version-status astp-status-development">' . esc_html($version_number) . '</span></p>';
        echo '<p><a href="#" class="button astp-view-version" data-version-id="' . esc_attr($tp_dev_version_id) . '">' . __('Edit Version', 'astp-versioning') . '</a></p>';
        echo '<p><a href="#" class="button button-primary astp-publish-version" data-version-id="' . esc_attr($tp_dev_version_id) . '" data-post-id="' . esc_attr($post->ID) . '" data-document-type="tp">' . __('Publish Version', 'astp-versioning') . '</a></p>';
    } else {
        echo '<p><em>' . __('No version in development.', 'astp-versioning') . '</em></p>';
        
        // Version creation controls
        echo '<hr>';
        echo '<h4>' . __('Create New Version', 'astp-versioning') . '</h4>';
        echo '<p>' . __('Select version type:', 'astp-versioning') . '</p>';
        echo '<select id="astp-tp-version-type" class="astp-version-type">
            <option value="minor">' . __('Minor Update', 'astp-versioning') . '</option>
            <option value="major">' . __('Major Release', 'astp-versioning') . '</option>
        </select>';
        echo '<p><a href="#" class="button button-primary astp-create-version" data-post-id="' . esc_attr($post->ID) . '" data-document-type="tp">' . __('Create New Version', 'astp-versioning') . '</a></p>';
    }
    
    echo '</div>'; // End development version
    
    // TP Version history link
    echo '<hr>';
    echo '<p><a href="#" class="button astp-view-version-history" data-post-id="' . esc_attr($post->ID) . '" data-document-type="tp">' . __('View TP Version History', 'astp-versioning') . '</a></p>';
    
    echo '</div>'; // End TP section
    
    // Legacy section for backwards compatibility - can be removed once migration is complete
    if ($current_version_id || $dev_version_id) {
        echo '<hr class="astp-section-divider">';
        echo '<div class="astp-version-section astp-legacy-section">';
        echo '<h3>' . __('Legacy Versions (Pre-Document Type Separation)', 'astp-versioning') . '</h3>';
        
        if ($current_version_id) {
            $version_number = get_post_meta($current_version_id, 'version_number', true);
            echo '<p><strong>' . __('Current Published:', 'astp-versioning') . '</strong> ' . esc_html($version_number) . '</p>';
        }
        
        if ($dev_version_id) {
            $version_number = get_post_meta($dev_version_id, 'version_number', true);
            echo '<p><strong>' . __('In Development:', 'astp-versioning') . '</strong> ' . esc_html($version_number) . '</p>';
        }
        
        echo '</div>'; // End legacy section
    }
    
    echo '</div>'; // End version controls
    
    // Add CSS for the new metabox layout
    echo '<style>
        .astp-version-section {
            margin-bottom: 20px;
            padding-bottom: 10px;
        }
        .astp-section-divider {
            margin: 20px 0;
            border-top: 1px solid #ddd;
        }
        .astp-ccg-section h3 {
            color: #2271b1;
        }
        .astp-tp-section h3 {
            color: #3c6e21;
        }
        .astp-legacy-section {
            opacity: 0.7;
        }
        .astp-legacy-section h3 {
            color: #888;
            font-size: 14px;
        }
    </style>';
}

/**
 * Add regenerate changelog button to the metabox
 * 
 * @param WP_Post $post The current post object
 */
function astp_add_regenerate_changelog_button($post) {
    // Only add on test method post type
    if (!$post || $post->post_type !== 'astp_test_method') {
        return;
    }
    
    // Get current version info
    $ccg_current_version = get_post_meta($post->ID, 'ccg_current_published_version', true);
    $tp_current_version = get_post_meta($post->ID, 'tp_current_published_version', true);
    
    // Get the current page URL for the form action
    $current_url = add_query_arg(array(), admin_url('post.php?post=' . $post->ID . '&action=edit'));
    
    echo '<div class="astp-admin-action-box">';
    echo '<p>Use this to regenerate the changelog if it\'s not showing your changes correctly.</p>';
    
    echo '<form method="post" action="' . esc_url($current_url) . '">';
    wp_nonce_field('astp_regenerate_changelog', 'astp_regenerate_changelog_nonce');
    echo '<input type="hidden" name="post_id" value="' . esc_attr($post->ID) . '">';
    
    echo '<div class="astp-form-row">';
    echo '<label for="doc_type">Document Type:</label>';
    echo '<select name="doc_type" id="doc_type">';
    echo '<option value="ccg">Certification Companion Guide</option>';
    echo '<option value="tp">Test Procedure</option>';
    echo '</select>';
    echo '</div>';
    
    echo '<div class="astp-form-row">';
    echo '<input type="submit" name="astp_regenerate_changelog" class="button button-primary" value="Regenerate Changelog">';
    echo '</div>';
    
    echo '</form>';
    echo '</div>';
}

/**
 * Handle the regenerate changelog form submission
 */
function astp_handle_regenerate_changelog() {
    // Check if form was submitted
    if (!isset($_POST['astp_regenerate_changelog'])) {
        return;
    }
    
    error_log('Regenerate changelog form submitted');
    
    // Verify nonce
    if (!isset($_POST['astp_regenerate_changelog_nonce']) || !wp_verify_nonce($_POST['astp_regenerate_changelog_nonce'], 'astp_regenerate_changelog')) {
        error_log('Security check failed in regenerate changelog');
        wp_die('Security check failed');
    }
    
    // Get post ID
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    
    if (!$post_id) {
        error_log('No post ID provided in regenerate changelog');
        wp_die('No post ID provided');
    }
    
    error_log('Processing regenerate changelog for post ID: ' . $post_id);
    
    // Get document type
    $doc_type = isset($_POST['doc_type']) ? sanitize_text_field($_POST['doc_type']) : '';
    
    if (!in_array($doc_type, ['ccg', 'tp'])) {
        error_log('Invalid document type in regenerate changelog: ' . $doc_type);
        wp_die('Invalid document type');
    }
    
    error_log('Document type for regenerate changelog: ' . $doc_type);
    
    // Get the current version
    $version_key = $doc_type . '_current_published_version';
    $version_id = get_post_meta($post_id, $version_key, true);
    
    error_log('Current version ID from meta ' . $version_key . ': ' . $version_id);
    
    if (!$version_id) {
        // Get the latest version
        $versions_key = $doc_type . '_versions_history';
        $versions = get_post_meta($post_id, $versions_key, true);
        
        error_log('No current version found, checking versions history: ' . print_r($versions, true));
        
        if (is_array($versions) && !empty($versions)) {
            $version_id = $versions[count($versions) - 1];
            error_log('Using latest version from history: ' . $version_id);
        }
    }
    
    if (!$version_id) {
        error_log('No version found for document type: ' . $doc_type);
        wp_die('No version found for this document type. Please create and publish a version first.');
    }
    
    // Get the previous version
    $previous_version_id = get_post_meta($version_id, 'previous_version_id', true);
    
    error_log('Previous version ID: ' . $previous_version_id);
    
    if (!$previous_version_id) {
        // This is the first version, so no changelog
        error_log('No previous version found for version ID: ' . $version_id);
        wp_die('This appears to be the first version, so there is no previous version to compare with.');
    }
    
    // Regenerate the changelog
    error_log('Calling astp_create_changelog with version_id: ' . $version_id . ' and previous_version_id: ' . $previous_version_id);
    $changelog_id = astp_create_changelog($version_id, $previous_version_id);
    
    error_log('Result from astp_create_changelog: ' . $changelog_id);
    
    if ($changelog_id) {
        // Store the changelog ID in the version
        update_post_meta($version_id, 'changelog_id', $changelog_id);
        error_log('Updated version ' . $version_id . ' with changelog ID: ' . $changelog_id);
        
        // Redirect back with success message
        $redirect_url = add_query_arg('changelog_regenerated', '1', get_edit_post_link($post_id, 'raw'));
        error_log('Redirecting to: ' . $redirect_url);
        wp_redirect($redirect_url);
        exit;
    } else {
        error_log('Failed to create changelog for version: ' . $version_id);
        wp_die('Failed to regenerate changelog. Please check the error logs for details.');
    }
}
add_action('admin_init', 'astp_handle_regenerate_changelog');

/**
 * Display admin notice after regenerating changelog
 */
function astp_display_changelog_regenerated_notice() {
    if (isset($_GET['changelog_regenerated']) && $_GET['changelog_regenerated'] === '1') {
        echo '<div class="notice notice-success is-dismissible">';
        echo '<p><strong>Success!</strong> The changelog has been regenerated. You should now see the changes in the Changelog Block.</p>';
        echo '</div>';
    }
}
add_action('admin_notices', 'astp_display_changelog_regenerated_notice');

// New function: Regenerate Changelog admin page
function astp_regenerate_changelog_page() {
    if (!current_user_can('edit_posts')) {
        wp_die(__('You do not have sufficient permissions to access this page.', 'astp-versioning'));
    }
    
    // Get test methods for selection
    $test_methods = get_posts([
        'post_type' => 'astp_test_method',
        'posts_per_page' => -1,
        'orderby' => 'title',
        'order' => 'ASC',
    ]);
    
    // Get all versions for direct selection
    $all_versions = get_posts([
        'post_type' => 'astp_version',
        'posts_per_page' => -1,
        'orderby' => 'meta_value',
        'meta_key' => 'version_number',
        'order' => 'DESC',
    ]);
    
    // Process form if submitted
    $message = '';
    $message_type = '';
    
    if (isset($_POST['astp_direct_regenerate_changelog'])) {
        // Verify nonce
        if (!isset($_POST['astp_regenerate_changelog_nonce']) || 
            !wp_verify_nonce($_POST['astp_regenerate_changelog_nonce'], 'astp_regenerate_changelog')) {
            $message = 'Security check failed';
            $message_type = 'error';
        } else {
            // Get form data
            $version_id = isset($_POST['version_id']) ? intval($_POST['version_id']) : 0;
            $previous_version_id = isset($_POST['previous_version_id']) ? intval($_POST['previous_version_id']) : 0;
            
            if (!$version_id || !$previous_version_id) {
                $message = 'Please select both a version and a previous version';
                $message_type = 'error';
            } else {
                // Log what we're doing
                error_log('Directly regenerating changelog for version ' . $version_id . ' compared to ' . $previous_version_id);
                
                // Create the changelog
                $changelog_id = astp_create_changelog($version_id, $previous_version_id);
                
                if ($changelog_id) {
                    // Store the changelog ID in the version
                    update_post_meta($version_id, 'changelog_id', $changelog_id);
                    $message = 'Changelog successfully regenerated!';
                    $message_type = 'success';
                    
                    error_log('Successfully created changelog ID ' . $changelog_id . ' for version ' . $version_id);
                } else {
                    $message = 'Failed to regenerate changelog. Check error logs for details.';
                    $message_type = 'error';
                    
                    error_log('Failed to create changelog for version ' . $version_id);
                }
            }
        }
    }
    
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        
        <?php if ($message): ?>
            <div class="notice notice-<?php echo $message_type; ?> is-dismissible">
                <p><?php echo esc_html($message); ?></p>
            </div>
        <?php endif; ?>
        
        <div class="astp-regenerate-page">
            <h2>Method 1: By Test Method</h2>
            <p>Select a test method and document type to regenerate the changelog for its latest published version.</p>
            
            <form method="post" action="">
                <?php wp_nonce_field('astp_regenerate_changelog', 'astp_regenerate_changelog_nonce'); ?>
                
                <table class="form-table">
                    <tr>
                        <th><label for="post_id">Test Method:</label></th>
                        <td>
                            <select name="post_id" id="post_id" class="regular-text">
                                <option value="">-- Select Test Method --</option>
                                <?php foreach ($test_methods as $test_method): ?>
                                    <option value="<?php echo esc_attr($test_method->ID); ?>">
                                        <?php echo esc_html($test_method->post_title); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="doc_type">Document Type:</label></th>
                        <td>
                            <select name="doc_type" id="doc_type">
                                <option value="ccg">Certification Companion Guide</option>
                                <option value="tp">Test Procedure</option>
                            </select>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <input type="submit" name="astp_regenerate_changelog" class="button button-primary" value="Regenerate Changelog">
                </p>
            </form>
            
            <hr>
            
            <h2>Method 2: Direct Version Selection</h2>
            <p>Select specific versions to compare directly.</p>
            
            <form method="post" action="">
                <?php wp_nonce_field('astp_regenerate_changelog', 'astp_regenerate_changelog_nonce'); ?>
                
                <table class="form-table">
                    <tr>
                        <th><label for="version_id">Current Version:</label></th>
                        <td>
                            <select name="version_id" id="version_id" class="regular-text">
                                <option value="">-- Select Version --</option>
                                <?php foreach ($all_versions as $version): 
                                    $version_number = get_post_meta($version->ID, 'version_number', true);
                                    $doc_type = get_post_meta($version->ID, 'document_type', true);
                                    $doc_type_label = '';
                                    if ($doc_type == 'ccg') {
                                        $doc_type_label = ' [CCG]';
                                    } elseif ($doc_type == 'tp') {
                                        $doc_type_label = ' [TP]';
                                    }
                                    $parent_title = '';
                                    if ($version->post_parent) {
                                        $parent_title = get_the_title($version->post_parent) . ' - ';
                                    }
                                ?>
                                    <option value="<?php echo esc_attr($version->ID); ?>">
                                        <?php echo esc_html($parent_title . 'v' . $version_number . $doc_type_label); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="previous_version_id">Previous Version:</label></th>
                        <td>
                            <select name="previous_version_id" id="previous_version_id" class="regular-text">
                                <option value="">-- Select Previous Version --</option>
                                <?php foreach ($all_versions as $version): 
                                    $version_number = get_post_meta($version->ID, 'version_number', true);
                                    $doc_type = get_post_meta($version->ID, 'document_type', true);
                                    $doc_type_label = '';
                                    if ($doc_type == 'ccg') {
                                        $doc_type_label = ' [CCG]';
                                    } elseif ($doc_type == 'tp') {
                                        $doc_type_label = ' [TP]';
                                    }
                                    $parent_title = '';
                                    if ($version->post_parent) {
                                        $parent_title = get_the_title($version->post_parent) . ' - ';
                                    }
                                ?>
                                    <option value="<?php echo esc_attr($version->ID); ?>">
                                        <?php echo esc_html($parent_title . 'v' . $version_number . $doc_type_label); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <input type="submit" name="astp_direct_regenerate_changelog" class="button button-primary" value="Regenerate Changelog">
                </p>
            </form>
        </div>
    </div>
    <?php
}
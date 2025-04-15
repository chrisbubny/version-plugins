<?php
/**
 * Change Tracking Functions
 *
 * @package ASTP_Versioning
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Extract changes from WordPress revisions
 *
 * @param int $post_id       The ID of the content post
 * @param int $new_version_id The ID of the new version
 * @return int|bool The ID of the changelog post, or false on failure
 */
function astp_extract_changes_from_revisions($post_id, $new_version_id) {
    // Get all post revisions
    $revisions = wp_get_post_revisions($post_id);
    
    if (empty($revisions)) {
        return false;
    }
    
    // Get the current revision and previous version revision
    $current_revision = array_shift($revisions);
    $prev_version_id = get_field('previous_version', $new_version_id);
    
    // If no previous version, use the oldest revision
    if (!$prev_version_id) {
        $prev_revision = end($revisions);
    } else {
        // Find the revision closest to the previous version creation date
        $prev_version_date = get_field('release_date', $prev_version_id);
        $prev_revision = null;
        
        foreach ($revisions as $revision) {
            if (strtotime($revision->post_date) <= strtotime($prev_version_date)) {
                $prev_revision = $revision;
                break;
            }
        }
        
        // If no match found, use the oldest revision
        if (!$prev_revision) {
            $prev_revision = end($revisions);
        }
    }
    
    if (!$prev_revision) {
        return false;
    }
    
    // Use wp_text_diff to get the changes
    $content_diff = wp_text_diff(
        $prev_revision->post_content,
        $current_revision->post_content,
        ['show_split_view' => true]
    );
    
    // Create change log entry
    $changelog_id = wp_insert_post([
        'post_title' => 'Changes for ' . get_the_title($post_id) . ' v' . get_field('version_number', $new_version_id),
        'post_type' => 'astp_changelog',
        'post_content' => $content_diff,
        'post_status' => 'publish',
    ]);
    
    if (!is_wp_error($changelog_id)) {
        // Connect changelog to version
        update_field('changelog_version', $new_version_id, $changelog_id);
        
        // Process and categorize changes
        astp_categorize_changes($changelog_id, $prev_revision->post_content, $current_revision->post_content);
        
        return $changelog_id;
    }
    
    return false;
}

/**
 * Alternative implementation for MultiCollab integration
 * (Requires MultiCollab plugin to be installed)
 *
 * @param int $post_id       The ID of the content post
 * @param int $new_version_id The ID of the new version
 * @return int|bool The ID of the changelog post, or false on failure
 */
function astp_extract_changes_from_multicollab($post_id, $new_version_id) {
    // Check if MultiCollab plugin is active
    if (!function_exists('multicollab_get_tracked_changes')) {
        return false;
    }
    
    // Get changes from MultiCollab
    $tracked_changes = multicollab_get_tracked_changes($post_id);
    
    if (empty($tracked_changes)) {
        return false;
    }
    
    // Create change log entry
    $changelog_id = wp_insert_post([
        'post_title' => 'Changes for ' . get_the_title($post_id) . ' v' . get_field('version_number', $new_version_id),
        'post_type' => 'astp_changelog',
        'post_content' => isset($tracked_changes['html_diff']) ? $tracked_changes['html_diff'] : '',
        'post_status' => 'publish',
    ]);
    
    if (!is_wp_error($changelog_id)) {
        // Connect changelog to version
        update_field('changelog_version', $new_version_id, $changelog_id);
        
        // Initialize change details repeater
        $changes = [];
        
        // Loop through all changes and categorize them
        if (isset($tracked_changes['changes']) && is_array($tracked_changes['changes'])) {
            foreach ($tracked_changes['changes'] as $change) {
                $change_type = '';
                if (isset($change['type'])) {
                    if ($change['type'] === 'insert') {
                        $change_type = 'addition';
                    } elseif ($change['type'] === 'delete') {
                        $change_type = 'removal';
                    } else {
                        $change_type = 'amendment';
                    }
                }
                
                // Add change type term to changelog
                wp_set_object_terms($changelog_id, $change_type, 'change_type', true);
                
                // Add to repeater field
                $changes[] = [
                    'change_type' => $change_type,
                    'description' => isset($change['content']) ? $change['content'] : '',
                    'author' => isset($change['author']) ? $change['author'] : get_current_user_id(),
                ];
            }
        }
        
        // Save changes to repeater field
        update_field('changelog_changes', $changes, $changelog_id);
        
        return $changelog_id;
    }
    
    return false;
}

/**
 * Categorize changes into addition, removal, amendment
 *
 * @param int    $changelog_id The ID of the changelog post
 * @param string $old_content  The old content
 * @param string $new_content  The new content
 */
function astp_categorize_changes($changelog_id, $old_content, $new_content) {
    // Use simple heuristic to determine change types
    $has_additions = strlen($new_content) > strlen($old_content);
    $has_removals = strpos($old_content, '<del') !== false || strlen($new_content) < strlen($old_content);
    $has_amendments = strpos($new_content, '<ins') !== false && strpos($old_content, '<del') !== false;
    
    $change_types = [];
    
    if ($has_additions) {
        $change_types[] = 'addition';
    }
    
    if ($has_removals) {
        $change_types[] = 'removal';
    }
    
    if ($has_amendments) {
        $change_types[] = 'amendment';
    }
    
    // If no specific types detected, default to amendment
    if (empty($change_types)) {
        $change_types[] = 'amendment';
    }
    
    // Set terms for the changelog
    wp_set_object_terms($changelog_id, $change_types, 'change_type');
    
    // Extract and store specific changes
    $changes = [];
    
    // For additions
    if (in_array('addition', $change_types)) {
        preg_match_all('/<ins[^>]*>(.*?)<\/ins>/s', $new_content, $additions);
        if (!empty($additions[1])) {
            foreach ($additions[1] as $addition) {
                $changes[] = [
                    'change_type' => 'addition',
                    'description' => strip_tags($addition),
                    'author' => get_current_user_id(),
                ];
            }
        }
    }
    
    // For removals
    if (in_array('removal', $change_types)) {
        preg_match_all('/<del[^>]*>(.*?)<\/del>/s', $old_content, $removals);
        if (!empty($removals[1])) {
            foreach ($removals[1] as $removal) {
                $changes[] = [
                    'change_type' => 'removal',
                    'description' => strip_tags($removal),
                    'author' => get_current_user_id(),
                ];
            }
        }
    }
    
    // If no specific changes detected but we have amendment type
    if (empty($changes) && in_array('amendment', $change_types)) {
        $changes[] = [
            'change_type' => 'amendment',
            'description' => 'General content amendments',
            'author' => get_current_user_id(),
        ];
    }
    
    // Save changes to repeater field
    update_field('changelog_changes', $changes, $changelog_id);
}

/**
 * Get changelog for a specific version
 *
 * @param int $version_id The ID of the version post
 * @return WP_Post|bool The changelog post or false if none found
 */
function astp_fetch_version_changelog($version_id) {
    $changelogs = get_posts([
        'post_type' => 'astp_changelog',
        'meta_query' => [
            [
                'key' => 'changelog_version',
                'value' => $version_id,
            ],
        ],
        'posts_per_page' => 1,
    ]);
    
    return !empty($changelogs) ? $changelogs[0] : false;
} 
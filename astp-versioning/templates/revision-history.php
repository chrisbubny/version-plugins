<?php
/**
 * Revision History template
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Get parent post ID
$parent_id = astp_get_version_parent_id($post->ID);

if (!$parent_id) {
    return;
}

// Get all versions of this post
$versions = astp_get_all_versions($parent_id);

if (empty($versions)) {
    return;
}

// Current version is the first one
$current_version = $versions[0];

// Sort versions by version number (descending)
usort($versions, function($a, $b) {
    $a_version = get_post_meta($a->ID, 'version_number', true);
    $b_version = get_post_meta($b->ID, 'version_number', true);
    return version_compare($b_version, $a_version);
});

// Get current version info
$current_version_number = get_post_meta($current_version->ID, 'version_number', true);
$release_date = get_post_meta($current_version->ID, 'release_date', true);
$formatted_date = !empty($release_date) ? date('F j, Y', strtotime($release_date)) : '';

// Get changelog
$changelog = astp_get_changelog_for_version($current_version->ID);
$has_changes = false;

if ($changelog) {
    $changes_data = get_post_meta($changelog->ID, 'changes_data', true);
    $has_changes = !empty($changes_data) && is_array($changes_data);
}

?>
<div class="astp-revision-history">
    <h2>Revision History</h2>
    
    <?php if (count($versions) > 1): ?>
    <div class="astp-filter-controls">
        <span class="astp-filter-label">View Version:</span>
        <select id="astp-version-selector" class="astp-version-select">
            <?php foreach ($versions as $version): ?>
                <?php 
                $version_num = get_post_meta($version->ID, 'version_number', true);
                $version_date = get_post_meta($version->ID, 'release_date', true);
                $version_formatted_date = !empty($version_date) ? date('M j, Y', strtotime($version_date)) : '';
                $selected = ($version->ID === $current_version->ID) ? 'selected' : '';
                ?>
                <option value="<?php echo esc_attr($version->ID); ?>" <?php echo $selected; ?>>
                    Version <?php echo esc_html($version_num); ?> 
                    <?php if (!empty($version_formatted_date)): ?>
                    - <?php echo esc_html($version_formatted_date); ?>
                    <?php endif; ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <?php endif; ?>
    
    <div class="astp-revision-entry" id="astp-revision-content">
        <div class="astp-revision-header">
            <h3>Version <?php echo esc_html($current_version_number); ?></h3>
            <?php if (!empty($formatted_date)): ?>
            <div class="astp-revision-meta">
                Released: <?php echo esc_html($formatted_date); ?>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="astp-revision-changes">
            <?php if ($has_changes): ?>
                <?php astp_display_version_changes($current_version->ID, $parent_id); ?>
            <?php else: ?>
                <div class="astp-no-changes">No changes are available for this version.</div>
            <?php endif; ?>
        </div>
    </div>
</div> 
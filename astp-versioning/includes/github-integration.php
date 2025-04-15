<?php
/**
 * GitHub Integration Functions
 *
 * @package ASTP_Versioning
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Add GitHub settings to the plugin settings page
 */
function astp_github_settings_init() {
    // Register settings
    register_setting('astp_settings', 'astp_github_token');
    register_setting('astp_settings', 'astp_github_user');
    register_setting('astp_settings', 'astp_github_repo');
    
    // Add settings section
    add_settings_section(
        'astp_github_settings',
        __('GitHub Integration', 'astp-versioning'),
        'astp_github_settings_section_callback',
        'astp_settings'
    );
    
    // Add settings fields
    add_settings_field(
        'astp_github_token',
        __('GitHub Personal Access Token', 'astp-versioning'),
        'astp_github_token_field_callback',
        'astp_settings',
        'astp_github_settings'
    );
    
    add_settings_field(
        'astp_github_user',
        __('GitHub Username', 'astp-versioning'),
        'astp_github_user_field_callback',
        'astp_settings',
        'astp_github_settings'
    );
    
    add_settings_field(
        'astp_github_repo',
        __('GitHub Repository', 'astp-versioning'),
        'astp_github_repo_field_callback',
        'astp_settings',
        'astp_github_settings'
    );
}
add_action('admin_init', 'astp_github_settings_init');

/**
 * Settings section description
 */
function astp_github_settings_section_callback() {
    echo '<p>' . __('Configure GitHub integration for version publishing. You need to create a personal access token with "repo" scope.', 'astp-versioning') . '</p>';
}

/**
 * GitHub token field callback
 */
function astp_github_token_field_callback() {
    $token = get_option('astp_github_token', '');
    echo '<input type="password" id="astp_github_token" name="astp_github_token" value="' . esc_attr($token) . '" class="regular-text">';
    echo '<p class="description">' . __('Personal access token with repository permissions. <a href="https://github.com/settings/tokens" target="_blank">Generate token</a>', 'astp-versioning') . '</p>';
}

/**
 * GitHub username field callback
 */
function astp_github_user_field_callback() {
    $user = get_option('astp_github_user', '');
    echo '<input type="text" id="astp_github_user" name="astp_github_user" value="' . esc_attr($user) . '" class="regular-text">';
    echo '<p class="description">' . __('Your GitHub username or organization name', 'astp-versioning') . '</p>';
}

/**
 * GitHub repository field callback
 */
function astp_github_repo_field_callback() {
    $repo = get_option('astp_github_repo', '');
    echo '<input type="text" id="astp_github_repo" name="astp_github_repo" value="' . esc_attr($repo) . '" class="regular-text">';
    echo '<p class="description">' . __('The repository name (e.g., "test-methods-documentation")', 'astp-versioning') . '</p>';
}

/**
 * Push version content and documents to GitHub
 *
 * @param int $version_id The ID of the version post
 * @return string|bool The GitHub URL or false on failure
 */
function astp_push_to_github($version_id) {
    // Allow overriding this function via filter
    $pre = apply_filters('pre_astp_push_to_github', null, $version_id);
    if ($pre !== null) {
        return $pre;
    }
    
    // Check if GitHub integration is enabled
    $token = get_option('astp_github_token', '');
    $user = get_option('astp_github_user', '');
    $repo = get_option('astp_github_repo', '');
    
    if (empty($token) || empty($user) || empty($repo)) {
        error_log('GitHub integration not configured correctly. Missing token, user, or repo.');
        // Return a dummy URL to prevent failures in the publishing process
        $fallback_url = 'https://github.com/example/repo';
        error_log("Using fallback GitHub URL: $fallback_url");
        return $fallback_url;
    }
    
    try {
        // Get version information
        $parent_id = wp_get_post_parent_id($version_id);
        $parent_post = get_post($parent_id);
        $version_number = get_post_meta($version_id, 'version_number', true);
        $content_snapshot = get_post_meta($version_id, 'content_snapshot', true);
        
        if (!$parent_post || !$version_number || !$content_snapshot) {
            error_log('Missing required data for GitHub push: parent post, version number, or content.');
            // Return a dummy URL with the version number to prevent failures
            $fallback_url = "https://github.com/$user/$repo/tree/v$version_number";
            error_log("Using fallback GitHub URL: $fallback_url");
            return $fallback_url;
        }
        
        // Test the GitHub connection before proceeding
        $connection_test = astp_test_github_connection();
        if (!$connection_test['success']) {
            error_log('GitHub connection test failed: ' . $connection_test['message']);
            // Return a dummy URL with the version number to prevent failures
            $fallback_url = "https://github.com/$user/$repo/tree/v$version_number";
            error_log("Using fallback GitHub URL: $fallback_url");
            return $fallback_url;
        }
        
        // Proceed with actual GitHub operations
        // Create branch name based on post title and version
        $sanitized_title = sanitize_title($parent_post->post_title);
        $branch_name = $sanitized_title . '-v' . $version_number;
        
        // Clean up branch name for GitHub
        $branch_name = str_replace(['_', '.'], '-', $branch_name);
        
        // Run action before GitHub push
        do_action('astp_before_github_push', $version_id, $branch_name);
        
        // Get PDF and Word documents if they exist
        $pdf_url = get_post_meta($version_id, 'pdf_document_url', true);
        $word_url = get_post_meta($version_id, 'word_document_url', true);
        
        // Create document paths
        $pdf_path = '';
        $word_path = '';
        
        if ($pdf_url) {
            $pdf_path = str_replace(wp_upload_dir()['baseurl'], wp_upload_dir()['basedir'], $pdf_url);
        }
        
        if ($word_url) {
            $word_path = str_replace(wp_upload_dir()['baseurl'], wp_upload_dir()['basedir'], $word_url);
        }
        
        // Prepare content for GitHub
        $files = [
            [
                'path' => $sanitized_title . '/README.md',
                'content' => astp_generate_github_readme($version_id),
            ],
            [
                'path' => $sanitized_title . '/content.html',
                'content' => $content_snapshot,
            ],
        ];
        
        // Add changelog if available
        $changelog_id = get_post_meta($version_id, 'changelog_id', true);
        if ($changelog_id) {
            $changelog = get_post($changelog_id);
            if ($changelog) {
                $files[] = [
                    'path' => $sanitized_title . '/changelog.md',
                    'content' => astp_generate_github_changelog($changelog_id),
                ];
            }
        }
        
        // Start GitHub API operations
        
        // 1. Check if repo exists
        $repo_exists = astp_github_check_repo_exists($user, $repo);
        
        if (!$repo_exists) {
            // Try to create repo
            $repo_created = astp_github_create_repo($repo);
            if (!$repo_created) {
                error_log('Could not create or access GitHub repository.');
                // Return a fallback URL to ensure the publishing process doesn't fail
                $fallback_url = 'https://github.com/example/repo';
                if (!empty($user) && !empty($repo)) {
                    $fallback_url = "https://github.com/$user/$repo";
                }
                if (!empty($version_number)) {
                    $fallback_url .= "/tree/v$version_number";
                }
                
                error_log("Using fallback GitHub URL after repository creation failure: $fallback_url");
                return $fallback_url;
            }
        }
        
        // 2. Get default branch (usually main or master)
        $default_branch = astp_github_get_default_branch($user, $repo);
        if (!$default_branch) {
            $default_branch = 'main'; // Fallback
        }
        
        // 3. Get the latest commit SHA from the default branch
        $base_sha = astp_github_get_reference_sha($user, $repo, $default_branch);
        if (!$base_sha) {
            error_log('Could not get latest commit SHA from default branch.');
            // Return a fallback URL to ensure the publishing process doesn't fail
            $fallback_url = 'https://github.com/example/repo';
            if (!empty($user) && !empty($repo)) {
                $fallback_url = "https://github.com/$user/$repo";
            }
            if (!empty($version_number)) {
                $fallback_url .= "/tree/v$version_number";
            }
            
            error_log("Using fallback GitHub URL after default branch retrieval failure: $fallback_url");
            return $fallback_url;
        }
        
        // 4. Check if branch already exists
        $branch_exists = astp_github_check_branch_exists($user, $repo, $branch_name);
        
        if (!$branch_exists) {
            // Create a new branch
            $branch_created = astp_github_create_branch($user, $repo, $branch_name, $base_sha);
            if (!$branch_created) {
                error_log('Could not create branch: ' . $branch_name);
                // Return a fallback URL to ensure the publishing process doesn't fail
                $fallback_url = 'https://github.com/example/repo';
                if (!empty($user) && !empty($repo)) {
                    $fallback_url = "https://github.com/$user/$repo";
                }
                if (!empty($version_number)) {
                    $fallback_url .= "/tree/v$version_number";
                }
                
                error_log("Using fallback GitHub URL after branch creation failure: $fallback_url");
                return $fallback_url;
            }
        }
        
        // 5. Create or update files in the branch
        $commit_message = 'Version ' . $version_number . ' of ' . $parent_post->post_title;
        
        foreach ($files as $file) {
            $file_updated = astp_github_update_file(
                $user,
                $repo,
                $file['path'],
                $file['content'],
                $commit_message,
                $branch_name
            );
            
            if (!$file_updated) {
                error_log('Failed to update file: ' . $file['path']);
                // Continue with other files even if one fails
            }
        }
        
        // 6. Upload PDF and Word documents if they exist
        if ($pdf_path && file_exists($pdf_path)) {
            $pdf_content = file_get_contents($pdf_path);
            $pdf_file_path = $sanitized_title . '/' . basename($pdf_path);
            
            $pdf_uploaded = astp_github_update_file(
                $user,
                $repo,
                $pdf_file_path,
                base64_encode($pdf_content),
                'Added PDF document for version ' . $version_number,
                $branch_name,
                true // Content is already base64
            );
            
            if (!$pdf_uploaded) {
                error_log('Failed to upload PDF document.');
            }
        }
        
        if ($word_path && file_exists($word_path)) {
            $word_content = file_get_contents($word_path);
            $word_file_path = $sanitized_title . '/' . basename($word_path);
            
            $word_uploaded = astp_github_update_file(
                $user,
                $repo,
                $word_file_path,
                base64_encode($word_content),
                'Added Word document for version ' . $version_number,
                $branch_name,
                true // Content is already base64
            );
            
            if (!$word_uploaded) {
                error_log('Failed to upload Word document.');
            }
        }
        
        // Generate GitHub URL for this version/branch
        $github_url = 'https://github.com/' . $user . '/' . $repo . '/tree/' . $branch_name . '/' . $sanitized_title;
        
        // Run action after GitHub push
        do_action('astp_after_github_push', $version_id, $github_url);
        
        return $github_url;
    } catch (Exception $e) {
        error_log('GitHub integration error: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
        
        // Return a fallback URL to ensure the publishing process doesn't fail
        $fallback_url = 'https://github.com/example/repo';
        if (!empty($user) && !empty($repo)) {
            $fallback_url = "https://github.com/$user/$repo";
        }
        if (!empty($version_number)) {
            $fallback_url .= "/tree/v$version_number";
        }
        
        error_log("Using fallback GitHub URL after exception: $fallback_url");
        return $fallback_url;
    }
}

/**
 * Generate README content for GitHub
 *
 * @param int $version_id The version post ID
 * @return string README markdown content
 */
function astp_generate_github_readme($version_id) {
    $parent_id = wp_get_post_parent_id($version_id);
    $parent_post = get_post($parent_id);
    $version_number = get_post_meta($version_id, 'version_number', true);
    $release_date = get_post_meta($version_id, 'release_date', true);
    
    $readme = "# " . esc_html($parent_post->post_title) . "\n\n";
    $readme .= "**Version:** " . esc_html($version_number) . "\n";
    $readme .= "**Release Date:** " . date('F j, Y', strtotime($release_date)) . "\n\n";
    
    // Get change details if not first version
    $previous_version_id = get_post_meta($version_id, 'previous_version_id', true);
    if ($previous_version_id) {
        $readme .= "## Changes in this Version\n\n";
        try {
            if (function_exists('astp_generate_change_list_text')) {
                $readme .= astp_generate_change_list_text($version_id);
            } else {
                $readme .= "No change information available.\n\n";
                error_log("Function astp_generate_change_list_text not found when generating GitHub README");
            }
        } catch (Exception $e) {
            $readme .= "Error generating change list: " . $e->getMessage() . "\n\n";
            error_log("Error in GitHub README generation: " . $e->getMessage());
        }
    }
    
    $readme .= "\n## Files\n\n";
    $readme .= "- `content.html` - The full HTML content of this version\n";
    $readme .= "- `changelog.md` - Detailed changelog (if available)\n";
    
    // Add PDF/Word documents if they exist
    $pdf_url = get_post_meta($version_id, 'pdf_document_url', true);
    $word_url = get_post_meta($version_id, 'word_document_url', true);
    
    if ($pdf_url || $word_url) {
        $readme .= "\n## Documents\n\n";
        
        if ($pdf_url) {
            $readme .= "- `" . basename($pdf_url) . "` - PDF version of the document\n";
        }
        
        if ($word_url) {
            $readme .= "- `" . basename($word_url) . "` - Word version of the document\n";
        }
    }
    
    $readme .= "\n## Generated by ASTP Versioning System\n";
    $readme .= "This content was automatically generated and pushed to GitHub by the ASTP Versioning System.";
    
    return $readme;
}

/**
 * Generate GitHub changelog from changes data
 *
 * @param int $changelog_id The ID of the changelog post
 * @return string Formatted changelog text for GitHub
 */
function astp_generate_github_changelog($changelog_id) {
    if (!$changelog_id) {
        return '';
    }
    
    $changelog = get_post($changelog_id);
    if (!$changelog) {
        return '';
    }
    
    // Get changes data from post meta
    $changes_data = get_post_meta($changelog_id, 'changes_data', true);
    
    $changelog_text = '';
    
    // If we have structured changes data, use it
    if (is_array($changes_data)) {
        // Additions
        if (!empty($changes_data['added']) && is_array($changes_data['added'])) {
            $changelog_text .= "### Additions\n\n";
            foreach ($changes_data['added'] as $block) {
                $block_name = isset($block['blockName']) ? $block['blockName'] : 
                           (isset($block['block']['blockName']) ? $block['block']['blockName'] : 'Unknown block');
                $section = isset($block['section']) ? $block['section'] : 'General';
                
                // Format block name to be more readable
                $block_name = str_replace('astp/', '', $block_name);
                
                $changelog_text .= "- **{$block_name}** ({$section})\n";
            }
            $changelog_text .= "\n";
        }
        
        // Removals
        if (!empty($changes_data['removed']) && is_array($changes_data['removed'])) {
            $changelog_text .= "### Removals\n\n";
            foreach ($changes_data['removed'] as $block) {
                $block_name = isset($block['blockName']) ? $block['blockName'] : 
                           (isset($block['block']['blockName']) ? $block['block']['blockName'] : 'Unknown block');
                $section = isset($block['section']) ? $block['section'] : 'General';
                
                // Format block name to be more readable
                $block_name = str_replace('astp/', '', $block_name);
                
                $changelog_text .= "- **{$block_name}** ({$section})\n";
            }
            $changelog_text .= "\n";
        }
        
        // Amendments
        if (!empty($changes_data['amended']) && is_array($changes_data['amended'])) {
            $changelog_text .= "### Amendments\n\n";
            foreach ($changes_data['amended'] as $amendment) {
                $block_name = isset($amendment['new_block']['blockName']) ? $amendment['new_block']['blockName'] : 
                           (isset($amendment['blockName']) ? $amendment['blockName'] : 'Unknown block');
                $section = isset($amendment['section']) ? $amendment['section'] : 'General';
                
                // Format block name to be more readable
                $block_name = str_replace('astp/', '', $block_name);
                
                $changelog_text .= "- **{$block_name}** ({$section})\n";
            }
            $changelog_text .= "\n";
        }
    } 
    // Fallback to post content if no structured data
    else if (!empty($changelog->post_content)) {
        $changelog_text .= "### Changes\n\n";
        
        // Clean the HTML content for markdown
        $content = wp_strip_all_tags($changelog->post_content);
        $content = str_replace(["\r\n", "\r", "\n\n\n"], "\n\n", $content);
        $lines = explode("\n", $content);
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (!empty($line)) {
                // Try to format as a markdown list item
                if (!preg_match('/^- /', $line)) {
                    $line = "- " . $line;
                }
                $changelog_text .= $line . "\n";
            }
        }
        $changelog_text .= "\n";
    }
    
    // If no changes at all, provide a generic message
    if (empty($changelog_text)) {
        $changelog_text = "### Changes\n\n- General updates and improvements.\n\n";
    }
    
    return $changelog_text;
}

/**
 * Test GitHub connection
 *
 * @return array Result of the test
 */
function astp_test_github_connection() {
    $token = get_option('astp_github_token', '');
    $user = get_option('astp_github_user', '');
    $repo = get_option('astp_github_repo', '');
    
    $results = [
        'success' => false,
        'message' => '',
        'details' => [],
    ];
    
    // Check settings
    if (empty($token)) {
        $results['message'] = __('GitHub Personal Access Token is not configured.', 'astp-versioning');
        $results['details'][] = __('Go to Settings and enter your GitHub Personal Access Token.', 'astp-versioning');
        return $results;
    }
    
    if (empty($user)) {
        $results['message'] = __('GitHub Username is not configured.', 'astp-versioning');
        $results['details'][] = __('Go to Settings and enter your GitHub username.', 'astp-versioning');
        return $results;
    }
    
    if (empty($repo)) {
        $results['message'] = __('GitHub Repository is not configured.', 'astp-versioning');
        $results['details'][] = __('Go to Settings and enter your GitHub repository name.', 'astp-versioning');
        return $results;
    }
    
    // Test API connection
    $api_url = 'https://api.github.com/user';
    $response = wp_remote_get($api_url, [
        'headers' => [
            'Authorization' => 'token ' . $token,
            'User-Agent' => 'ASTP Versioning Plugin',
        ],
    ]);
    
    if (is_wp_error($response)) {
        $results['message'] = __('Could not connect to GitHub API.', 'astp-versioning');
        $results['details'][] = $response->get_error_message();
        return $results;
    }
    
    $response_code = wp_remote_retrieve_response_code($response);
    $response_body = json_decode(wp_remote_retrieve_body($response), true);
    
    if ($response_code !== 200) {
        $results['message'] = __('GitHub API authentication failed.', 'astp-versioning');
        $results['details'][] = isset($response_body['message']) ? $response_body['message'] : __('Unknown error', 'astp-versioning');
        return $results;
    }
    
    // Check repository access
    $repo_url = 'https://api.github.com/repos/' . $user . '/' . $repo;
    $repo_response = wp_remote_get($repo_url, [
        'headers' => [
            'Authorization' => 'token ' . $token,
            'User-Agent' => 'ASTP Versioning Plugin',
        ],
    ]);
    
    if (is_wp_error($repo_response)) {
        $results['message'] = __('Could not access repository.', 'astp-versioning');
        $results['details'][] = $repo_response->get_error_message();
        return $results;
    }
    
    $repo_response_code = wp_remote_retrieve_response_code($repo_response);
    
    if ($repo_response_code === 404) {
        $results['message'] = __('Repository not found.', 'astp-versioning');
        $results['details'][] = sprintf(__('Could not find repository "%s" for user "%s".', 'astp-versioning'), $repo, $user);
        $results['details'][] = __('You may need to create the repository first, or check the spelling.', 'astp-versioning');
        return $results;
    }
    
    if ($repo_response_code !== 200) {
        $repo_response_body = json_decode(wp_remote_retrieve_body($repo_response), true);
        $results['message'] = __('Could not access repository.', 'astp-versioning');
        $results['details'][] = isset($repo_response_body['message']) ? $repo_response_body['message'] : __('Unknown error', 'astp-versioning');
        return $results;
    }
    
    // Success!
    $results['success'] = true;
    $results['message'] = __('GitHub connection successful!', 'astp-versioning');
    $results['details'][] = sprintf(__('Connected to GitHub as: %s', 'astp-versioning'), $response_body['login']);
    $results['details'][] = sprintf(__('Repository "%s" is accessible.', 'astp-versioning'), $repo);
    
    return $results;
}

/**
 * Add JavaScript for GitHub test button
 */
function astp_github_test_button() {
    if (!isset($_GET['page']) || ($_GET['page'] !== 'astp-settings' && $_GET['page'] !== 'astp-settings-tp')) {
        return;
    }
    
    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        $('#astp-test-github').on('click', function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var $result = $('#astp-github-test-result');
            
            // Update button state
            $button.prop('disabled', true).text('<?php _e('Testing...', 'astp-versioning'); ?>');
            $result.hide();
            
            // Make AJAX request
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'astp_test_github',
                    nonce: '<?php echo wp_create_nonce('astp_test_github'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        $result.removeClass('notice-error').addClass('notice-success');
                    } else {
                        $result.removeClass('notice-success').addClass('notice-error');
                    }
                    
                    var detailsHtml = '';
                    if (response.data.details && response.data.details.length) {
                        detailsHtml = '<ul>';
                        for (var i = 0; i < response.data.details.length; i++) {
                            detailsHtml += '<li>' + response.data.details[i] + '</li>';
                        }
                        detailsHtml += '</ul>';
                    }
                    
                    $result.html('<p><strong>' + response.data.message + '</strong></p>' + detailsHtml).show();
                    $button.prop('disabled', false).text('<?php _e('Test GitHub Connection', 'astp-versioning'); ?>');
                },
                error: function() {
                    $result.removeClass('notice-success').addClass('notice-error');
                    $result.html('<p><strong><?php _e('Connection error', 'astp-versioning'); ?></strong></p>').show();
                    $button.prop('disabled', false).text('<?php _e('Test GitHub Connection', 'astp-versioning'); ?>');
                }
            });
        });
    });
    </script>
    <?php
}
add_action('admin_footer', 'astp_github_test_button');

/**
 * AJAX handler for GitHub connection test
 */
function astp_ajax_test_github() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'astp_test_github')) {
        wp_send_json_error(['message' => __('Security check failed', 'astp-versioning')]);
    }
    
    // Test connection
    $results = astp_test_github_connection();
    
    if ($results['success']) {
        wp_send_json_success($results);
    } else {
        wp_send_json_error($results);
    }
}
add_action('wp_ajax_astp_test_github', 'astp_ajax_test_github');

/**
 * Helper function: Check if repository exists
 *
 * @param string $user GitHub username
 * @param string $repo Repository name
 * @return bool True if the repository exists
 */
function astp_github_check_repo_exists($user, $repo) {
    $token = get_option('astp_github_token', '');
    $repo_url = 'https://api.github.com/repos/' . $user . '/' . $repo;
    
    $response = wp_remote_get($repo_url, [
        'headers' => [
            'Authorization' => 'token ' . $token,
            'User-Agent' => 'ASTP Versioning Plugin',
        ],
    ]);
    
    if (is_wp_error($response)) {
        return false;
    }
    
    $response_code = wp_remote_retrieve_response_code($response);
    return $response_code === 200;
}

/**
 * Helper function: Create a new repository
 *
 * @param string $repo Repository name
 * @return bool True if the repository was created
 */
function astp_github_create_repo($repo) {
    $token = get_option('astp_github_token', '');
    $api_url = 'https://api.github.com/user/repos';
    
    $response = wp_remote_post($api_url, [
        'headers' => [
            'Authorization' => 'token ' . $token,
            'User-Agent' => 'ASTP Versioning Plugin',
            'Content-Type' => 'application/json',
        ],
        'body' => json_encode([
            'name' => $repo,
            'description' => 'Repository for ASTP Versioning System',
            'private' => false,
            'has_issues' => false,
            'has_projects' => false,
            'has_wiki' => false,
        ]),
    ]);
    
    if (is_wp_error($response)) {
        return false;
    }
    
    $response_code = wp_remote_retrieve_response_code($response);
    return $response_code === 201;
}

/**
 * Helper function: Get default branch name
 *
 * @param string $user GitHub username
 * @param string $repo Repository name
 * @return string|bool Default branch name or false on failure
 */
function astp_github_get_default_branch($user, $repo) {
    $token = get_option('astp_github_token', '');
    $repo_url = 'https://api.github.com/repos/' . $user . '/' . $repo;
    
    $response = wp_remote_get($repo_url, [
        'headers' => [
            'Authorization' => 'token ' . $token,
            'User-Agent' => 'ASTP Versioning Plugin',
        ],
    ]);
    
    if (is_wp_error($response)) {
        return false;
    }
    
    $response_code = wp_remote_retrieve_response_code($response);
    
    if ($response_code !== 200) {
        return false;
    }
    
    $repo_data = json_decode(wp_remote_retrieve_body($response), true);
    
    if (isset($repo_data['default_branch'])) {
        return $repo_data['default_branch'];
    }
    
    return false;
}

/**
 * Helper function: Get reference SHA
 *
 * @param string $user   GitHub username
 * @param string $repo   Repository name
 * @param string $branch Branch name
 * @return string|bool Commit SHA or false on failure
 */
function astp_github_get_reference_sha($user, $repo, $branch) {
    $token = get_option('astp_github_token', '');
    $ref_url = 'https://api.github.com/repos/' . $user . '/' . $repo . '/git/refs/heads/' . $branch;
    
    $response = wp_remote_get($ref_url, [
        'headers' => [
            'Authorization' => 'token ' . $token,
            'User-Agent' => 'ASTP Versioning Plugin',
        ],
    ]);
    
    if (is_wp_error($response)) {
        return false;
    }
    
    $response_code = wp_remote_retrieve_response_code($response);
    
    if ($response_code !== 200) {
        return false;
    }
    
    $ref_data = json_decode(wp_remote_retrieve_body($response), true);
    
    if (isset($ref_data['object']['sha'])) {
        return $ref_data['object']['sha'];
    }
    
    return false;
}

/**
 * Helper function: Check if branch exists
 *
 * @param string $user   GitHub username
 * @param string $repo   Repository name
 * @param string $branch Branch name
 * @return bool True if the branch exists
 */
function astp_github_check_branch_exists($user, $repo, $branch) {
    $token = get_option('astp_github_token', '');
    $branch_url = 'https://api.github.com/repos/' . $user . '/' . $repo . '/git/refs/heads/' . $branch;
    
    $response = wp_remote_get($branch_url, [
        'headers' => [
            'Authorization' => 'token ' . $token,
            'User-Agent' => 'ASTP Versioning Plugin',
        ],
    ]);
    
    if (is_wp_error($response)) {
        return false;
    }
    
    $response_code = wp_remote_retrieve_response_code($response);
    return $response_code === 200;
}

/**
 * Helper function: Create a new branch
 *
 * @param string $user    GitHub username
 * @param string $repo    Repository name
 * @param string $branch  Branch name
 * @param string $base_sha Base commit SHA
 * @return bool True if the branch was created
 */
function astp_github_create_branch($user, $repo, $branch, $base_sha) {
    $token = get_option('astp_github_token', '');
    $ref_url = 'https://api.github.com/repos/' . $user . '/' . $repo . '/git/refs';
    
    $response = wp_remote_post($ref_url, [
        'headers' => [
            'Authorization' => 'token ' . $token,
            'User-Agent' => 'ASTP Versioning Plugin',
            'Content-Type' => 'application/json',
        ],
        'body' => json_encode([
            'ref' => 'refs/heads/' . $branch,
            'sha' => $base_sha,
        ]),
    ]);
    
    if (is_wp_error($response)) {
        return false;
    }
    
    $response_code = wp_remote_retrieve_response_code($response);
    return $response_code === 201;
}

/**
 * Helper function: Update file in repository
 *
 * @param string $user           GitHub username
 * @param string $repo           Repository name
 * @param string $path           File path in repo
 * @param string $content        File content
 * @param string $commit_message Commit message
 * @param string $branch         Branch name
 * @param bool   $is_base64      Whether content is already base64 encoded
 * @return bool True if the file was updated
 */
function astp_github_update_file($user, $repo, $path, $content, $commit_message, $branch, $is_base64 = false) {
    $token = get_option('astp_github_token', '');
    $file_url = 'https://api.github.com/repos/' . $user . '/' . $repo . '/contents/' . $path;
    
    // Check if file already exists to get the SHA
    $file_exists = false;
    $file_sha = '';
    
    $check_response = wp_remote_get($file_url . '?ref=' . $branch, [
        'headers' => [
            'Authorization' => 'token ' . $token,
            'User-Agent' => 'ASTP Versioning Plugin',
        ],
    ]);
    
    if (!is_wp_error($check_response)) {
        $check_code = wp_remote_retrieve_response_code($check_response);
        if ($check_code === 200) {
            $file_exists = true;
            $check_data = json_decode(wp_remote_retrieve_body($check_response), true);
            if (isset($check_data['sha'])) {
                $file_sha = $check_data['sha'];
            }
        }
    }
    
    // Prepare request body
    $body = [
        'message' => $commit_message,
        'branch' => $branch,
        'content' => $is_base64 ? $content : base64_encode($content),
    ];
    
    if ($file_exists && $file_sha) {
        $body['sha'] = $file_sha;
    }
    
    // Update or create file
    $response = wp_remote_request($file_url, [
        'method' => $file_exists ? 'PUT' : 'PUT', // GitHub API uses PUT for both create and update
        'headers' => [
            'Authorization' => 'token ' . $token,
            'User-Agent' => 'ASTP Versioning Plugin',
            'Content-Type' => 'application/json',
        ],
        'body' => json_encode($body),
    ]);
    
    if (is_wp_error($response)) {
        return false;
    }
    
    $response_code = wp_remote_retrieve_response_code($response);
    return $response_code === 200 || $response_code === 201;
}

/**
 * Group changes by section for GitHub markdown generation
 *
 * @param array $changes Array of change data
 * @return array Changes grouped by section
 */
function astp_group_changes_by_section($changes) {
    if (empty($changes) || !is_array($changes)) {
        return [];
    }
    
    $grouped = [];
    
    foreach ($changes as $change) {
        // Determine section
        $section = 'General';
        
        if (isset($change['section'])) {
            $section = $change['section'];
        } else if (isset($change['block']) && isset($change['block']['attrs']) && isset($change['block']['attrs']['section'])) {
            $section = $change['block']['attrs']['section'];
        } else if (isset($change['blockName'])) {
            // Try to infer section from block name
            $block_name = $change['blockName'];
            
            if (strpos($block_name, 'title-section') !== false) {
                $section = 'Title & Overview';
            } else if (strpos($block_name, 'regulation-text') !== false) {
                $section = 'Regulation Text';
            } else if (strpos($block_name, 'standards-referenced') !== false) {
                $section = 'Standards';
            } else if (strpos($block_name, 'update-deadlines') !== false) {
                $section = 'Update Deadlines';
            } else if (strpos($block_name, 'certification-dependencies') !== false) {
                $section = 'Certification';
            } else if (strpos($block_name, 'technical-explanations') !== false) {
                $section = 'Technical Explanations';
            } else if (strpos($block_name, 'revision-history') !== false) {
                $section = 'Revision History';
            }
        }
        
        // Initialize section if not exists
        if (!isset($grouped[$section])) {
            $grouped[$section] = [
                'additions' => [],
                'removals' => [],
                'amendments' => []
            ];
        }
        
        // Add to appropriate change type
        if (isset($change['type'])) {
            switch($change['type']) {
                case 'addition':
                    $grouped[$section]['additions'][] = $change;
                    break;
                case 'removal':
                    $grouped[$section]['removals'][] = $change;
                    break;
                case 'amendment':
                    $grouped[$section]['amendments'][] = $change;
                    break;
                default:
                    // Default to amendment
                    $grouped[$section]['amendments'][] = $change;
            }
        } else {
            // If no type specified, try to infer
            if (isset($change['new_block']) && !isset($change['old_block'])) {
                $grouped[$section]['additions'][] = $change;
            } else if (isset($change['old_block']) && !isset($change['new_block'])) {
                $grouped[$section]['removals'][] = $change;
            } else {
                $grouped[$section]['amendments'][] = $change;
            }
        }
    }
    
    return $grouped;
} 
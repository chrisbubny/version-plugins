<?php
/**
 * GitHub Debug and Testing Functions
 * 
 * @package ASTP_Versioning
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Enhanced GitHub push implementation using GitHub API Library (like the older version)
 *
 * @param int $version_id The version ID to push to GitHub
 * @return string|bool The GitHub URL or false on failure
 */
function astp_enhanced_push_to_github($version_id) {
    // Start debug logging
    error_log("------- GITHUB DEBUG: Starting enhanced GitHub push for version ID: $version_id -------");
    
    // Check if GitHub integration is enabled
    $github_token = get_option('astp_github_token');
    $github_user = get_option('astp_github_user');
    $github_repo = get_option('astp_github_repo');
    
    // Log GitHub settings (but hide most of the token for security)
    $token_debug = !empty($github_token) ? substr($github_token, 0, 4) . '...' . substr($github_token, -4) : 'empty';
    error_log("GitHub Settings - User: '$github_user', Repo: '$github_repo', Token: $token_debug");
    
    if (empty($github_token) || empty($github_user) || empty($github_repo)) {
        error_log("ERROR: GitHub integration not properly configured.");
        return false;
    }
    
    // Check if GitHub API library is available
    if (!class_exists('\Github\Client')) {
        // Look for the Composer autoloader
        $autoloader_paths = [
            dirname(__DIR__, 2) . '/vendor/autoload.php',
            dirname(__DIR__, 3) . '/vendor/autoload.php',
        ];
        
        $autoloader_loaded = false;
        
        foreach ($autoloader_paths as $path) {
            if (file_exists($path)) {
                error_log("Loading autoloader from: $path");
                require_once $path;
                $autoloader_loaded = true;
                break;
            }
        }
        
        // If we still can't load GitHub API, return false
        if (!$autoloader_loaded || !class_exists('\Github\Client')) {
            error_log("ERROR: GitHub API library not found. Please install it using Composer.");
            return false;
        }
    }
    
    // Get version metadata
    $version_post = get_post($version_id);
    if (!$version_post) {
        error_log("ERROR: Version post not found: $version_id");
        return false;
    }
    
    // Get version details using both get_field (ACF) and get_post_meta
    // to ensure we get the data regardless of which method is used
    $version_number = get_post_meta($version_id, 'version_number', true);
    if (empty($version_number) && function_exists('get_field')) {
        $version_number = get_field('version_number', $version_id);
    }
    
    $parent_id = wp_get_post_parent_id($version_id);
    if (!$parent_id && function_exists('get_field')) {
        $parent_content_id = get_field('parent_content', $version_id);
        if ($parent_content_id) {
            $parent_id = $parent_content_id;
        } else {
            $parent_id = get_post_meta($version_id, 'parent_content', true);
        }
    }
    
    error_log("Version number: $version_number, Parent ID: $parent_id");
    
    // Get parent post
    $parent_post = $parent_id ? get_post($parent_id) : null;
    if (!$parent_post) {
        error_log("ERROR: Parent post not found for version ID: $version_id");
        return false;
    }
    
    // Get content snapshot
    $content_snapshot = get_post_meta($version_id, 'content_snapshot', true);
    if (empty($content_snapshot) && function_exists('get_field')) {
        $content_snapshot = get_field('content_snapshot', $version_id);
    }
    
    // CRITICAL FIX: Check if content_snapshot is an array and convert to string if needed
    if (is_array($content_snapshot)) {
        error_log("WARNING: Content snapshot is an array, converting to string");
        // Convert to JSON if it's an array 
        $content_snapshot = json_encode($content_snapshot);
        if (!$content_snapshot) {
            error_log("ERROR: Failed to convert content snapshot to string");
            $content_snapshot = "Content could not be properly formatted";
        }
    }
    
    if (empty($content_snapshot)) {
        error_log("WARNING: Empty content snapshot, using parent post content");
        $content_snapshot = $parent_post->post_content;
    }
    
    try {
        // Initialize GitHub client
        error_log("Initializing GitHub Client");
        $client = new \Github\Client();
        
        // Authenticate with GitHub using a token
        error_log("Authenticating with GitHub");
        $client->authenticate($github_token, null, \Github\Client::AUTH_ACCESS_TOKEN);
        
        // Create branch name based on post title and version
        $sanitized_title = sanitize_title($parent_post->post_title);
        
        // CRITICAL FIX: Ensure sanitized title is never empty and doesn't start with a hyphen
        if (empty($sanitized_title) || $sanitized_title === '-') {
            $sanitized_title = 'content-' . $version_id;
            error_log("WARNING: Empty sanitized title, using fallback: $sanitized_title");
        } elseif (substr($sanitized_title, 0, 1) === '-') {
            $sanitized_title = 'content' . $sanitized_title;
            error_log("WARNING: Sanitized title starts with hyphen, fixed to: $sanitized_title");
        }
        
        $branch_name = $sanitized_title . '-v' . $version_number;
        
        // Clean up branch name for GitHub
        $branch_name = str_replace(['_', '.'], '-', $branch_name);
        error_log("Branch name: $branch_name");
        
        // Get main/master branch reference
        try {
            error_log("Getting main branch reference");
            $reference = $client->api('gitData')->references()->show($github_user, $github_repo, 'heads/main');
            $base_branch = 'main';
        } catch (\Exception $e) {
            // Try master if main doesn't exist
            try {
                error_log("Main branch not found, trying master branch");
                $reference = $client->api('gitData')->references()->show($github_user, $github_repo, 'heads/master');
                $base_branch = 'master';
            } catch (\Exception $e) {
                error_log("ERROR: GitHub repository main/master branch not found: " . $e->getMessage());
                return false;
            }
        }
        
        $sha = $reference['object']['sha'];
        error_log("Base branch: $base_branch, SHA: $sha");
        
        // Create new branch
        try {
            error_log("Creating new branch: $branch_name");
            $client->api('gitData')->references()->create($github_user, $github_repo, [
                'ref' => 'refs/heads/' . $branch_name,
                'sha' => $sha
            ]);
            error_log("Branch created successfully");
        } catch (\Exception $e) {
            // Branch might already exist
            error_log("Warning: Error creating branch (might already exist): " . $e->getMessage());
            // Continue anyway
        }
        
        // Prepare content files
        error_log("Preparing content files");
        
        // 1. README.md file
        $readme_content = "# " . $parent_post->post_title . " (v" . $version_number . ")\n\n";
        $readme_content .= "This content was automatically pushed to GitHub by the ASTP Versioning System.\n\n";
        $readme_content .= "## Details\n\n";
        $readme_content .= "* **Version:** " . $version_number . "\n";
        $readme_content .= "* **Date:** " . date('F j, Y') . "\n";
        
        try {
            error_log("Creating README.md file");
            // FIXED: Create a valid path for README.md
            $readme_path = !empty($sanitized_title) ? $sanitized_title . '/README.md' : 'README.md';
            error_log("README path: $readme_path");
            
            $client->api('repo')->contents()->create(
                $github_user,
                $github_repo,
                $readme_path,
                $readme_content,
                'Add README for ' . $parent_post->post_title . ' v' . $version_number,
                $branch_name
            );
            error_log("README.md file created successfully");
        } catch (\Exception $e) {
            error_log("Warning: Error creating README.md file: " . $e->getMessage());
            // Try to update the file instead
            try {
                $file_info = $client->api('repo')->contents()->show(
                    $github_user,
                    $github_repo,
                    $readme_path,
                    $branch_name
                );
                
                $client->api('repo')->contents()->update(
                    $github_user,
                    $github_repo,
                    $readme_path,
                    $readme_content,
                    'Update README for ' . $parent_post->post_title . ' v' . $version_number,
                    $file_info['sha'],
                    $branch_name
                );
                error_log("README.md file updated successfully");
            } catch (\Exception $e2) {
                error_log("Error updating README.md file: " . $e2->getMessage());
                // Continue anyway
            }
        }
        
        // 2. Main content file
        try {
            error_log("Creating content.html file");
            // FIXED: Create a valid path for content.html
            $content_path = !empty($sanitized_title) ? $sanitized_title . '/content.html' : 'content.html';
            error_log("Content path: $content_path");
            
            // FIXED: Ensure content is a string
            if (!is_string($content_snapshot)) {
                if (is_array($content_snapshot) || is_object($content_snapshot)) {
                    $content_snapshot = json_encode($content_snapshot);
                } else {
                    $content_snapshot = (string)$content_snapshot;
                }
            }
            
            $client->api('repo')->contents()->create(
                $github_user,
                $github_repo,
                $content_path,
                $content_snapshot,
                'Add content for ' . $parent_post->post_title . ' v' . $version_number,
                $branch_name
            );
            error_log("content.html file created successfully");
        } catch (\Exception $e) {
            error_log("Warning: Error creating content.html file: " . $e->getMessage());
            // Try to update the file instead
            try {
                $file_info = $client->api('repo')->contents()->show(
                    $github_user,
                    $github_repo,
                    $content_path,
                    $branch_name
                );
                
                $client->api('repo')->contents()->update(
                    $github_user,
                    $github_repo,
                    $content_path,
                    $content_snapshot,
                    'Update content for ' . $parent_post->post_title . ' v' . $version_number,
                    $file_info['sha'],
                    $branch_name
                );
                error_log("content.html file updated successfully");
            } catch (\Exception $e2) {
                error_log("Error updating content.html file: " . $e2->getMessage());
                // Continue anyway
            }
        }
        
        // 3. PDF file
        $pdf_url = get_post_meta($version_id, 'pdf_document_url', true);
        if (empty($pdf_url) && function_exists('get_field')) {
            $pdf_url = get_field('pdf_document_url', $version_id);
        }
        
        if (!empty($pdf_url)) {
            $pdf_path = str_replace(wp_upload_dir()['baseurl'], wp_upload_dir()['basedir'], $pdf_url);
            if (file_exists($pdf_path)) {
                error_log("PDF file found at: $pdf_path");
                try {
                    $pdf_content = base64_encode(file_get_contents($pdf_path));
                    // FIXED: Create a valid path for PDF files
                    $pdf_filename = !empty($sanitized_title) ? $sanitized_title . '/' . basename($pdf_path) : basename($pdf_path);
                    error_log("PDF filename: $pdf_filename");
                    
                    error_log("Uploading PDF file: $pdf_filename");
                    $client->api('repo')->contents()->create(
                        $github_user,
                        $github_repo,
                        $pdf_filename,
                        $pdf_content,
                        'Add PDF for ' . $parent_post->post_title . ' v' . $version_number,
                        $branch_name,
                        true // Binary file
                    );
                    error_log("PDF file uploaded successfully");
                } catch (\Exception $e) {
                    error_log("Error uploading PDF file: " . $e->getMessage());
                    // Continue anyway
                }
            } else {
                error_log("PDF file not found at path: $pdf_path");
            }
        } else {
            error_log("No PDF URL found for version ID: $version_id");
        }
        
        // 4. Word file
        $word_url = get_post_meta($version_id, 'word_document_url', true);
        if (empty($word_url) && function_exists('get_field')) {
            $word_url = get_field('word_document_url', $version_id);
        }
        
        if (!empty($word_url)) {
            $word_path = str_replace(wp_upload_dir()['baseurl'], wp_upload_dir()['basedir'], $word_url);
            if (file_exists($word_path)) {
                error_log("Word file found at: $word_path");
                try {
                    $word_content = base64_encode(file_get_contents($word_path));
                    // FIXED: Create a valid path for Word files
                    $word_filename = !empty($sanitized_title) ? $sanitized_title . '/' . basename($word_path) : basename($word_path);
                    error_log("Word filename: $word_filename");
                    
                    error_log("Uploading Word file: $word_filename");
                    $client->api('repo')->contents()->create(
                        $github_user,
                        $github_repo,
                        $word_filename,
                        $word_content,
                        'Add Word document for ' . $parent_post->post_title . ' v' . $version_number,
                        $branch_name,
                        true // Binary file
                    );
                    error_log("Word file uploaded successfully");
                } catch (\Exception $e) {
                    error_log("Error uploading Word file: " . $e->getMessage());
                    // Continue anyway
                }
            } else {
                error_log("Word file not found at path: $word_path");
            }
        } else {
            error_log("No Word URL found for version ID: $version_id");
        }
        
        // Generate GitHub URL for this version/branch
        $github_url = 'https://github.com/' . $github_user . '/' . $github_repo . '/tree/' . $branch_name;
        if (!empty($sanitized_title)) {
            $github_url .= '/' . $sanitized_title;
        }
        error_log("GitHub push successful - URL: $github_url");
        
        // Update the GitHub URL in the version post meta
        update_post_meta($version_id, 'github_url', $github_url);
        error_log("Updated github_url meta for version ID: $version_id");
        
        error_log("------- GITHUB DEBUG: GitHub push completed successfully -------");
        
        return $github_url;
    } catch (\Exception $e) {
        error_log("FATAL ERROR in GitHub push: " . $e->getMessage());
        error_log("Exception trace: " . $e->getTraceAsString());
        error_log("------- GITHUB DEBUG: GitHub push failed -------");
        return false;
    }
}

/**
 * Hook function to use the enhanced GitHub push
 */
function astp_use_enhanced_github_push($version_id) {
    error_log("Overriding default GitHub push with enhanced version for version ID: $version_id");
    return astp_enhanced_push_to_github($version_id);
}

// Hook into our push function to override the default one
add_filter('pre_astp_push_to_github', 'astp_use_enhanced_github_push', 10, 1);

/**
 * Add GitHub debugging info to admin
 */
function astp_add_github_debug_info() {
    global $post;
    
    // Only show on version post type
    if (!$post || get_post_type($post) !== 'astp_version') {
        return;
    }
    
    ?>
    <div class="notice notice-info">
        <p><strong>GitHub Debug:</strong> Enhanced GitHub integration is active. Check your PHP error log for detailed GitHub push information.</p>
        <p>
            <button type="button" id="astp-test-github-debug" class="button button-secondary">Test GitHub Push Now</button>
            <span id="github-debug-result" style="margin-left: 10px; display: none;"></span>
        </p>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        $('#astp-test-github-debug').on('click', function() {
            var $button = $(this);
            var $result = $('#github-debug-result');
            
            $button.prop('disabled', true).text('Testing...');
            $result.hide();
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'astp_test_github_debug',
                    version_id: <?php echo $post->ID; ?>,
                    nonce: '<?php echo wp_create_nonce('astp_github_debug'); ?>'
                },
                success: function(response) {
                    $button.prop('disabled', false).text('Test GitHub Push Now');
                    
                    if (response.success) {
                        $result.html('Success! <a href="' + response.data.url + '" target="_blank">View on GitHub</a>').css('color', 'green').show();
                    } else {
                        $result.text('Failed: ' + (response.data.message || 'Unknown error')).css('color', 'red').show();
                    }
                },
                error: function() {
                    $button.prop('disabled', false).text('Test GitHub Push Now');
                    $result.text('Connection error').css('color', 'red').show();
                }
            });
        });
    });
    </script>
    <?php
}
add_action('admin_notices', 'astp_add_github_debug_info');

/**
 * AJAX handler for GitHub debug testing
 */
function astp_test_github_debug_ajax() {
    // Check nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'astp_github_debug')) {
        wp_send_json_error(['message' => 'Security check failed']);
        return;
    }
    
    // Check version ID
    $version_id = isset($_POST['version_id']) ? intval($_POST['version_id']) : 0;
    if (!$version_id) {
        wp_send_json_error(['message' => 'Missing version ID']);
        return;
    }
    
    // Check permissions
    if (!current_user_can('edit_post', $version_id)) {
        wp_send_json_error(['message' => 'Permission denied']);
        return;
    }
    
    // Test GitHub push
    $result = astp_enhanced_push_to_github($version_id);
    
    if ($result) {
        wp_send_json_success(['url' => $result]);
    } else {
        wp_send_json_error(['message' => 'GitHub push failed. Check error log for details.']);
    }
}
add_action('wp_ajax_astp_test_github_debug', 'astp_test_github_debug_ajax'); 
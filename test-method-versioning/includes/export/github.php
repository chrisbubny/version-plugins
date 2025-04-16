<?php
/**
 * Handles GitHub integration
 */

/**
 * Push document to GitHub
 */
function tm_push_to_github($post_id, $version, $document_type) {
	// Get GitHub settings
	$github_repo = get_option('tm_github_repo');
	$github_token = get_option('tm_github_token');
	$github_branch = get_option('tm_github_branch', 'main');
	
	// Check if GitHub integration is enabled
	if (empty($github_repo) || empty($github_token)) {
		return false;
	}
	
	// Get documents
	$documents = get_post_meta($post_id, 'documents', true);
	if (!is_array($documents) || !isset($documents[$version][$document_type])) {
		return false;
	}
	
	$attachment_id = $documents[$version][$document_type];
	$file_path = get_attached_file($attachment_id);
	
	if (!file_exists($file_path)) {
		return false;
	}
	
	// Get post data
	$post = get_post($post_id);
	$title = sanitize_title($post->post_title);
	
	// Create GitHub API request
	$filename = basename($file_path);
	$repo_path = $title . '/' . $version . '/' . $filename;
	
	// Read file content
	$file_content = file_get_contents($file_path);
	$content_base64 = base64_encode($file_content);
	
	// Create commit message
	$commit_message = sprintf(
		'Add %s document for %s version %s',
		strtoupper($document_type),
		$post->post_title,
		$version
	);
	
	// GitHub API URL
	$github_api_url = 'https://api.github.com/repos/' . $github_repo . '/contents/' . $repo_path;
	
	// Set up request
	$args = array(
		'headers' => array(
			'Authorization' => 'token ' . $github_token,
			'Accept' => 'application/vnd.github.v3+json',
			'User-Agent' => 'WordPress/' . get_bloginfo('version')
		),
		'body' => json_encode(array(
			'message' => $commit_message,
			'content' => $content_base64,
			'branch' => $github_branch
		))
	);
	
	// Check if file already exists
	$response = wp_remote_get($github_api_url, array(
		'headers' => array(
			'Authorization' => 'token ' . $github_token,
			'Accept' => 'application/vnd.github.v3+json',
			'User-Agent' => 'WordPress/' . get_bloginfo('version')
		)
	));
	
	if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
		// File exists, update it with current sha
		$file_data = json_decode(wp_remote_retrieve_body($response), true);
		$args['body'] = json_encode(array(
			'message' => $commit_message,
			'content' => $content_base64,
			'sha' => $file_data['sha'],
			'branch' => $github_branch
		));
		
		$response = wp_remote_request($github_api_url, array(
			'method' => 'PUT',
			'headers' => $args['headers'],
			'body' => $args['body']
		));
	} else {
		// File doesn't exist, create it
		$response = wp_remote_request($github_api_url, array(
			'method' => 'PUT',
			'headers' => $args['headers'],
			'body' => $args['body']
		));
	}
	
	// Check response
	if (is_wp_error($response)) {
		return false;
	}
	
	$response_code = wp_remote_retrieve_response_code($response);
	if ($response_code !== 200 && $response_code !== 201) {
		return false;
	}
	
	// Store GitHub URL in meta
	$github_urls = get_post_meta($post_id, 'github_urls', true);
	if (!is_array($github_urls)) {
		$github_urls = array();
	}
	
	$response_body = json_decode(wp_remote_retrieve_body($response), true);
	
	if (isset($response_body['content']['html_url'])) {
		$github_urls[$version][$document_type] = $response_body['content']['html_url'];
		update_post_meta($post_id, 'github_urls', $github_urls);
	}
	
	do_action('tm_github_pushed', $post_id, $version, $document_type, $response_body);
	
	return true;
}
add_action('tm_document_generated', 'tm_push_to_github', 10, 3);
<?php
/**
 * Migration utility for existing plugin data
 */

/**
 * Migrate from astp-versioning plugin
 */
function tm_migrate_from_astp_versioning() {
	// Get all astp_test_method posts
	$args = array(
		'post_type' => 'astp_test_method',
		'posts_per_page' => -1,
		'post_status' => 'any',
	);
	
	$posts = get_posts($args);
	
	if (empty($posts)) {
		return array(
			'status' => 'success',
			'message' => __('No posts found to migrate.', 'test-method-versioning'),
			'count' => 0
		);
	}
	
	$migrated_count = 0;
	
	foreach ($posts as $post) {
		// Create new test_method post
		$new_post_args = array(
			'post_title' => $post->post_title,
			'post_content' => $post->post_content,
			'post_status' => $post->post_status,
			'post_author' => $post->post_author,
			'post_date' => $post->post_date,
			'post_modified' => $post->post_modified,
			'post_excerpt' => $post->post_excerpt,
			'post_type' => 'test_method',
		);
		
		$new_post_id = wp_insert_post($new_post_args);
		
		if (!is_wp_error($new_post_id)) {
			// Migrate post meta
			$meta_keys = get_post_custom_keys($post->ID);
			
			if (!empty($meta_keys)) {
				foreach ($meta_keys as $meta_key) {
					// Skip internal WordPress meta
					if (strpos($meta_key, '_') === 0) {
						continue;
					}
					
					$meta_values = get_post_meta($post->ID, $meta_key, false);
					
					foreach ($meta_values as $meta_value) {
						add_post_meta($new_post_id, $meta_key, $meta_value);
					}
				}
			}
			
			$migrated_count++;
		}
	}
	
	return array(
		'status' => 'success',
		'message' => sprintf(__('Successfully migrated %d posts.', 'test-method-versioning'), $migrated_count),
		'count' => $migrated_count
	);
}

/**
 * Migrate from workflow plugin
 */
function tm_migrate_from_workflow() {
	// Get all test_method posts from workflow plugin
	$args = array(
		'post_type' => 'test_method',
		'posts_per_page' => -1,
		'post_status' => 'any',
		'meta_query' => array(
			array(
				'key' => 'workflow_status',
				'compare' => 'EXISTS',
			),
		),
	);
	
	$posts = get_posts($args);
	
	if (empty($posts)) {
		return array(
			'status' => 'success',
			'message' => __('No workflow data found to migrate.', 'test-method-versioning'),
			'count' => 0
		);
	}
	
	$migrated_count = 0;
	
	foreach ($posts as $post) {
		// Get workflow metadata
		$workflow_status = get_post_meta($post->ID, 'workflow_status', true);
		$approvals = get_post_meta($post->ID, 'approvals', true);
		
		// Check if post exists in the new system
		$existing_post = get_page_by_title($post->post_title, OBJECT, 'test_method');
		
		if ($existing_post) {
			// Update workflow data
			update_post_meta($existing_post->ID, 'workflow_status', $workflow_status);
			
			if (!empty($approvals)) {
				update_post_meta($existing_post->ID, 'approvals', $approvals);
			}
			
			$migrated_count++;
		}
	}
	
	return array(
		'status' => 'success',
		'message' => sprintf(__('Successfully migrated workflow data for %d posts.', 'test-method-versioning'), $migrated_count),
		'count' => $migrated_count
	);
}

/**
 * Run all migrations
 */
function tm_run_migrations() {
	$results = array();
	
	// Run migrations
	$results['astp_versioning'] = tm_migrate_from_astp_versioning();
	$results['workflow'] = tm_migrate_from_workflow();
	
	return $results;
}
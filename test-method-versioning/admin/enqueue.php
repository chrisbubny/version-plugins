<?php
/**
 * Enqueue scripts and styles for the plugin.
 */

/**
 * Enqueue dashboard scripts and styles
 */
function tm_enqueue_dashboard_assets($hook) {
	// Only load on dashboard page
	if (!isset($_GET['page']) || $_GET['page'] !== 'test-method-dashboard') {
		return;
	}
	
	// CSS
	wp_enqueue_style(
		'tm-dashboard-styles',
		TM_PLUGIN_URL . 'assets/css/dashboard.css',
		array(),
		TM_VERSION
	);
	
	// JavaScript
	wp_enqueue_script(
		'tm-dashboard-scripts',
		TM_PLUGIN_URL . 'assets/js/dashboard.js',
		array('jquery', 'wp-api-fetch', 'wp-i18n', 'wp-components', 'wp-element'),
		TM_VERSION,
		true
	);
	
	// Data for JavaScript
	wp_localize_script('tm-dashboard-scripts', 'tmDashboardData', array(
		'restURL' => rest_url('tm-versioning/v1'),
		'nonce' => wp_create_nonce('wp_rest'),
		'userCaps' => array(
			'canApprove' => current_user_can('approve_test_method'),
			'canPublish' => current_user_can('publish_test_method'),
			'canUnlock' => current_user_can('unlock_test_method')
		)
	));
}
add_action('admin_enqueue_scripts', 'tm_enqueue_dashboard_assets');

/**
 * Register sidebar assets
 */
function tm_enqueue_sidebar_scripts() {
	global $post;
	
	// Check if we're editing a test_method post
	if (!is_admin() || !$post || get_post_type($post) !== 'test_method') {
		return;
	}
	
	// Enqueue the script
	wp_enqueue_script(
		'tm-versioning-sidebar',
		TM_PLUGIN_URL . 'assets/js/sidebar.js',
		array('wp-plugins', 'wp-edit-post', 'wp-element', 'wp-components', 'wp-api-fetch', 'wp-data', 'wp-i18n'),
		TM_VERSION,
		true
	);
	
	// Add data for the script
	$current_user = wp_get_current_user();
	
	wp_localize_script('tm-versioning-sidebar', 'tmVersioningData', array(
		'restURL' => rest_url('tm-versioning/v1'),
		'nonce' => wp_create_nonce('wp_rest'),
		'userRoles' => $current_user->roles,
		'userCaps' => array(
			'canApprove' => current_user_can('approve_test_method'),
			'canPublish' => current_user_can('publish_test_method'),
			'canUnlock' => current_user_can('unlock_test_method')
		)
	));
}
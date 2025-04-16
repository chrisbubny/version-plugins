<?php
/**
 * Fired during plugin activation
 */
class TM_Activator {
	/**
	 * Activates the plugin
	 */
	public static function activate() {
		// Register post type
		require_once TM_PLUGIN_DIR . 'includes/post-types.php';
		tm_register_post_types();
		
		// Register custom roles
		require_once TM_PLUGIN_DIR . 'includes/roles.php';
		tm_register_roles();
		tm_add_caps_to_roles();
		
		// Register post statuses
		require_once TM_PLUGIN_DIR . 'includes/workflow/states.php';
		tm_register_post_statuses();
		
		// Create asset directories
		if (!file_exists(TM_PLUGIN_DIR . 'assets/js')) {
			mkdir(TM_PLUGIN_DIR . 'assets/js', 0755, true);
		}
		
		if (!file_exists(TM_PLUGIN_DIR . 'assets/css')) {
			mkdir(TM_PLUGIN_DIR . 'assets/css', 0755, true);
		}
		
		// Flush rewrite rules
		flush_rewrite_rules();
	}
}
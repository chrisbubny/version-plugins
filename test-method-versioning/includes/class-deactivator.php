<?php
/**
 * Fired during plugin deactivation
 */
class TM_Deactivator {
	/**
	 * Deactivates the plugin
	 */
	public static function deactivate() {
		// Remove custom roles
		require_once TM_PLUGIN_DIR . 'includes/roles.php';
		tm_remove_roles();
		
		// Flush rewrite rules
		flush_rewrite_rules();
	}
}
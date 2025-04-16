<?php
/**
 * Plugin Name: Test Method Versioning and Workflow
 * Plugin URI: https://healthit.gov/
 * Description: Block-based versioning system with comprehensive workflow management for Test Method posts
 * Version: 1.0.0
 * Author: Spire Communications
 * Text Domain: test-method-versioning
 * Domain Path: /languages
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
	die;
}

define('TM_VERSION', '1.0.0');
define('TM_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('TM_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * The code that runs during plugin activation.
 */
function activate_test_method_versioning() {
	require_once TM_PLUGIN_DIR . 'includes/class-activator.php';
	TM_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_test_method_versioning() {
	require_once TM_PLUGIN_DIR . 'includes/class-deactivator.php';
	TM_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_test_method_versioning');
register_deactivation_hook(__FILE__, 'deactivate_test_method_versioning');

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require TM_PLUGIN_DIR . 'includes/class-test-method-versioning.php';

/**
 * Begins execution of the plugin.
 */
function run_test_method_versioning() {
	$plugin = new Test_Method_Versioning();
	$plugin->run();
}
run_test_method_versioning();
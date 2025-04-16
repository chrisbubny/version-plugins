<?php
/**
 * The core plugin class.
 *
 * Defines internationalization, admin hooks, and public hooks
 */
class Test_Method_Versioning {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @var TM_Loader
	 */
	protected $loader;

	/**
	 * Define the core functionality of the plugin.
	 */
	public function __construct() {
		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();
		$this->define_block_hooks();
		$this->define_workflow_hooks();
		$this->define_versioning_hooks();
		$this->define_export_hooks();
	}

	/**
	 * Load the required dependencies for this plugin.
	 */
	private function load_dependencies() {
		// Core
		require_once TM_PLUGIN_DIR . 'includes/class-loader.php';
		require_once TM_PLUGIN_DIR . 'includes/class-i18n.php';
		
		// Post Types and Taxonomy
		require_once TM_PLUGIN_DIR . 'includes/post-types.php';
		require_once TM_PLUGIN_DIR . 'includes/meta.php';
		
		// Roles
		require_once TM_PLUGIN_DIR . 'includes/roles.php';
		
		// Admin
		require_once TM_PLUGIN_DIR . 'admin/class-admin.php';
		require_once TM_PLUGIN_DIR . 'admin/dashboard.php';
		require_once TM_PLUGIN_DIR . 'admin/enqueue.php';
		
		// Blocks
		require_once TM_PLUGIN_DIR . 'includes/blocks/register-blocks.php';
		
		// Versioning
		require_once TM_PLUGIN_DIR . 'includes/versioning/block-versioning.php';
		require_once TM_PLUGIN_DIR . 'includes/versioning/version-control.php';
		require_once TM_PLUGIN_DIR . 'includes/versioning/changelog.php';
		
		// Workflow
		require_once TM_PLUGIN_DIR . 'includes/workflow/states.php';
		require_once TM_PLUGIN_DIR . 'includes/workflow/approvals.php';
		require_once TM_PLUGIN_DIR . 'includes/workflow/locking.php';
		require_once TM_PLUGIN_DIR . 'includes/workflow/notifications.php';
		
		// Export
		require_once TM_PLUGIN_DIR . 'includes/export/word.php';
		require_once TM_PLUGIN_DIR . 'includes/export/pdf.php';
		require_once TM_PLUGIN_DIR . 'includes/export/github.php';
		
		// REST API
		require_once TM_PLUGIN_DIR . 'includes/rest-api/endpoints.php';
		
		$this->loader = new TM_Loader();
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 */
	private function define_admin_hooks() {
		$admin = new TM_Admin();
		
		$this->loader->add_action('admin_menu', $admin, 'add_menu_pages');
		$this->loader->add_action('admin_enqueue_scripts', $admin, 'enqueue_styles');
		$this->loader->add_action('admin_enqueue_scripts', $admin, 'enqueue_scripts');
		
		// Dashboard hooks
		if (function_exists('tm_add_dashboard_menu')) {
			$this->loader->add_action('admin_menu', null, 'tm_add_dashboard_menu');
		}
	}

	/**
	 * Register all of the hooks related to block functionality
	 */
	private function define_block_hooks() {
		if (function_exists('tm_register_blocks')) {
			$this->loader->add_action('init', null, 'tm_register_blocks');
		}
		
		if (function_exists('tm_enqueue_sidebar_scripts')) {
			$this->loader->add_action('enqueue_block_editor_assets', null, 'tm_enqueue_sidebar_scripts');
		}
	}

	/**
	 * Register all of the hooks related to workflow functionality
	 */
	private function define_workflow_hooks() {
		// Register post statuses
		if (function_exists('tm_register_post_statuses')) {
			$this->loader->add_action('init', null, 'tm_register_post_statuses');
		}
		
		// Status transition hooks
		if (function_exists('tm_handle_status_transition')) {
			$this->loader->add_action('transition_post_status', null, 'tm_handle_status_transition', 10, 3);
		}
		
		// Approval hooks
		if (function_exists('tm_process_approval')) {
			$this->loader->add_action('save_post_test_method', null, 'tm_process_approval', 10, 3);
		}
		
		// Locking hooks
		if (function_exists('tm_check_post_lock')) {
			$this->loader->add_filter('wp_check_post_lock_window', null, 'tm_check_post_lock', 10, 2);
		}
		
		if (function_exists('tm_filter_edit_capabilities')) {
			$this->loader->add_filter('user_has_cap', null, 'tm_filter_edit_capabilities', 10, 3);
		}
		
		// Notification hooks
		if (function_exists('tm_notify_submitted_for_review')) {
			$this->loader->add_action('tm_post_submitted_for_review', null, 'tm_notify_submitted_for_review');
		}
		
		if (function_exists('tm_notify_approved')) {
			$this->loader->add_action('tm_post_approved', null, 'tm_notify_approved');
		}
		
		if (function_exists('tm_notify_rejected')) {
			$this->loader->add_action('tm_post_rejected', null, 'tm_notify_rejected');
		}
	}

	/**
	 * Register all of the hooks related to versioning functionality
	 */
	private function define_versioning_hooks() {
		// Version saving hooks
		if (function_exists('tm_process_version_update')) {
			$this->loader->add_action('save_post_test_method', null, 'tm_process_version_update', 10, 3);
		}
		
		// Changelog hooks
		if (function_exists('tm_generate_changelog')) {
			$this->loader->add_action('tm_version_updated', null, 'tm_generate_changelog', 10, 3);
		}
		
		// Block versioning hooks
		if (function_exists('tm_process_block_for_versioning')) {
			$this->loader->add_filter('render_block', null, 'tm_process_block_for_versioning', 10, 2);
		}
	}

	/**
	 * Register all of the hooks related to export functionality
	 */
	private function define_export_hooks() {
		// Document generation hooks
		if (function_exists('tm_generate_word_document')) {
			$this->loader->add_action('tm_version_published', null, 'tm_generate_word_document', 10, 2);
		}
		
		if (function_exists('tm_generate_pdf_document')) {
			$this->loader->add_action('tm_version_published', null, 'tm_generate_pdf_document', 10, 2);
		}
		
		// GitHub hooks
		if (function_exists('tm_push_to_github')) {
			$this->loader->add_action('tm_document_generated', null, 'tm_push_to_github', 10, 3);
		}
	}

	/**
	 * Register all of the hooks related to internationalization
	 */
	private function set_locale() {
		$i18n = new TM_i18n();
		$this->loader->add_action('plugins_loaded', $i18n, 'load_plugin_textdomain');
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 */
	private function define_public_hooks() {
		// Add any public-facing hooks here
	}

	/**
	 * Run the loader to execute all the hooks
	 */
	public function run() {
		$this->loader->run();
	}
}
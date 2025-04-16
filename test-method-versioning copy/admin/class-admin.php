<?php
/**
 * The admin-specific functionality of the plugin.
 */
class TM_Admin {

	/**
	 * Register the stylesheets for the admin area.
	 */
	public function enqueue_styles() {
		// Only enqueue on test_method post type admin pages
		$screen = get_current_screen();
		if (!$screen || $screen->post_type !== 'test_method') {
			return;
		}
		
		wp_enqueue_style(
			'test-method-versioning-admin',
			TM_PLUGIN_URL . 'admin/css/admin.css',
			array(),
			TM_VERSION,
			'all'
		);
	}

	/**
	 * Register the JavaScript for the admin area.
	 */
	public function enqueue_scripts() {
		// Only enqueue on test_method post type admin pages
		$screen = get_current_screen();
		if (!$screen || $screen->post_type !== 'test_method') {
			return;
		}
		
		wp_enqueue_script(
			'test-method-versioning-admin',
			TM_PLUGIN_URL . 'admin/js/admin.js',
			array('jquery'),
			TM_VERSION,
			false
		);
	}

	/**
	 * Add menu pages
	 */
	public function add_menu_pages() {
		// Add settings page
		add_submenu_page(
			'edit.php?post_type=test_method',
			__('Test Method Settings', 'test-method-versioning'),
			__('Settings', 'test-method-versioning'),
			'manage_options',
			'test-method-settings',
			array($this, 'render_settings_page')
		);
	}
	
	/**
	 * Render settings page
	 */
	public function render_settings_page() {
		// Check user permissions
		if (!current_user_can('manage_options')) {
			wp_die(__('You do not have sufficient permissions to access this page.', 'test-method-versioning'));
		}
		
		// Process form submission
		if (isset($_POST['tm_save_settings']) && check_admin_referer('tm_settings_nonce')) {
			// Save GitHub settings
			if (isset($_POST['tm_github_repo'])) {
				update_option('tm_github_repo', sanitize_text_field($_POST['tm_github_repo']));
			}
			
			if (isset($_POST['tm_github_token'])) {
				update_option('tm_github_token', sanitize_text_field($_POST['tm_github_token']));
			}
			
			if (isset($_POST['tm_github_branch'])) {
				update_option('tm_github_branch', sanitize_text_field($_POST['tm_github_branch']));
			}
			
			// Display success message
			echo '<div class="notice notice-success is-dismissible"><p>';
			_e('Settings saved successfully.', 'test-method-versioning');
			echo '</p></div>';
		}
		
		// Get current settings
		$github_repo = get_option('tm_github_repo', '');
		$github_token = get_option('tm_github_token', '');
		$github_branch = get_option('tm_github_branch', 'main');
		
		// Render settings form
		?>
		<div class="wrap">
			<h1><?php _e('Test Method Settings', 'test-method-versioning'); ?></h1>
			
			<form method="post" action="">
				<?php wp_nonce_field('tm_settings_nonce'); ?>
				
				<h2><?php _e('GitHub Integration', 'test-method-versioning'); ?></h2>
				<p><?php _e('Configure GitHub integration for document export.', 'test-method-versioning'); ?></p>
				
				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="tm_github_repo"><?php _e('GitHub Repository', 'test-method-versioning'); ?></label>
						</th>
						<td>
							<input type="text" id="tm_github_repo" name="tm_github_repo" value="<?php echo esc_attr($github_repo); ?>" class="regular-text" placeholder="username/repository" />
							<p class="description"><?php _e('Enter the GitHub repository in the format "username/repository".', 'test-method-versioning'); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="tm_github_token"><?php _e('GitHub Access Token', 'test-method-versioning'); ?></label>
						</th>
						<td>
							<input type="password" id="tm_github_token" name="tm_github_token" value="<?php echo esc_attr($github_token); ?>" class="regular-text" />
							<p class="description"><?php _e('Enter your GitHub personal access token with repository access.', 'test-method-versioning'); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="tm_github_branch"><?php _e('GitHub Branch', 'test-method-versioning'); ?></label>
						</th>
						<td>
							<input type="text" id="tm_github_branch" name="tm_github_branch" value="<?php echo esc_attr($github_branch); ?>" class="regular-text" placeholder="main" />
							<p class="description"><?php _e('Enter the branch to push documents to (default: main).', 'test-method-versioning'); ?></p>
						</td>
					</tr>
				</table>
				
				<p class="submit">
					<input type="submit" name="tm_save_settings" class="button button-primary" value="<?php _e('Save Settings', 'test-method-versioning'); ?>" />
				</p>
			</form>
		</div>
		<?php
	}
}
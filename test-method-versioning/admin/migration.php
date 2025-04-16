<?php
/**
 * Migration admin page
 */
function tm_add_migration_page() {
	add_submenu_page(
		'edit.php?post_type=test_method',
		__('Migration Tool', 'test-method-versioning'),
		__('Migration Tool', 'test-method-versioning'),
		'manage_options',
		'test-method-migration',
		'tm_render_migration_page'
	);
}
add_action('admin_menu', 'tm_add_migration_page');

/**
 * Render migration page
 */
function tm_render_migration_page() {
	// Check user permissions
	if (!current_user_can('manage_options')) {
		wp_die(__('You do not have sufficient permissions to access this page.', 'test-method-versioning'));
	}
	
	// Process migration if submitted
	$migration_results = null;
	
	if (isset($_POST['tm_run_migration']) && check_admin_referer('tm_migration_nonce')) {
		require_once TM_PLUGIN_DIR . 'includes/migration.php';
		$migration_results = tm_run_migrations();
	}
	
	// Check if plugins exist
	$astp_versioning_exists = class_exists('ASTP_Versioning') || post_type_exists('astp_test_method');
	$workflow_exists = class_exists('Test_Method_Workflow') || post_type_exists('test_method');
	
	?>
	<div class="wrap">
		<h1><?php _e('Test Method Migration Tool', 'test-method-versioning'); ?></h1>
		
		<div class="notice notice-warning">
			<p><strong><?php _e('Warning:', 'test-method-versioning'); ?></strong> <?php _e('This migration tool will convert data from the astp-versioning and workflow plugins to the new unified plugin. It is recommended to backup your database before proceeding.', 'test-method-versioning'); ?></p>
		</div>
		
		<?php if ($migration_results) : ?>
			<div class="notice notice-success">
				<p><strong><?php _e('Migration Results:', 'test-method-versioning'); ?></strong></p>
				<ul>
					<?php if (isset($migration_results['astp_versioning'])) : ?>
						<li><?php echo $migration_results['astp_versioning']['message']; ?></li>
					<?php endif; ?>
					
					<?php if (isset($migration_results['workflow'])) : ?>
						<li><?php echo $migration_results['workflow']['message']; ?></li>
					<?php endif; ?>
				</ul>
			</div>
		<?php endif; ?>
		
		<div class="card">
			<h2><?php _e('Available Plugins for Migration', 'test-method-versioning'); ?></h2>
			
			<table class="widefat" style="margin-top: 15px;">
				<thead>
					<tr>
						<th><?php _e('Plugin', 'test-method-versioning'); ?></th>
						<th><?php _e('Status', 'test-method-versioning'); ?></th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td><?php _e('ASTP Versioning Plugin', 'test-method-versioning'); ?></td>
						<td><?php echo $astp_versioning_exists ? 
							'<span style="color: green;">' . __('Detected', 'test-method-versioning') . '</span>' : 
							'<span style="color: red;">' . __('Not Found', 'test-method-versioning') . '</span>'; 
						?></td>
					</tr>
					<tr>
						<td><?php _e('Test Method Workflow Plugin', 'test-method-versioning'); ?></td>
						<td><?php echo $workflow_exists ? 
							'<span style="color: green;">' . __('Detected', 'test-method-versioning') . '</span>' : 
							'<span style="color: red;">' . __('Not Found', 'test-method-versioning') . '</span>'; 
						?></td>
					</tr>
				</tbody>
			</table>
		</div>
		
		<div class="card" style="margin-top: 20px;">
			<h2><?php _e('Run Migration', 'test-method-versioning'); ?></h2>
			
			<p><?php _e('Click the button below to migrate data from the existing plugins to the new unified plugin.', 'test-method-versioning'); ?></p>
			
			<form method="post" action="">
				<?php wp_nonce_field('tm_migration_nonce'); ?>
				<input type="submit" name="tm_run_migration" class="button button-primary" value="<?php _e('Run Migration', 'test-method-versioning'); ?>" 
					<?php echo (!$astp_versioning_exists && !$workflow_exists) ? 'disabled' : ''; ?>>
				
				<?php if (!$astp_versioning_exists && !$workflow_exists) : ?>
					<p><em><?php _e('No existing plugins detected. Migration is not necessary.', 'test-method-versioning'); ?></em></p>
				<?php endif; ?>
			</form>
		</div>
	</div>
	<?php
}
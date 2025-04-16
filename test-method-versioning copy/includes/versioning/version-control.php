<?php
/**
 * Handles version control
 */

/**
 * Create a new version
 */
function tm_create_version($post_id, $version_type = 'minor') {
	// Get post content
	$post = get_post($post_id);
	$content = $post->post_content;
	
	// Parse blocks
	$blocks = parse_blocks($content);
	
	// Get current versions
	$ccg_version = get_post_meta($post_id, 'ccg_version', true) ?: '1.0.0';
	$tp_version = get_post_meta($post_id, 'tp_version', true) ?: '1.0.0';
	
	// Generate new versions based on version type
	$new_ccg_version = tm_increment_version($ccg_version, $version_type);
	$new_tp_version = tm_increment_version($tp_version, $version_type);
	
	// Store previous version content
	$previous_versions = get_post_meta($post_id, 'versions', true);
	if (!is_array($previous_versions)) {
		$previous_versions = array();
	}
	
	// Add current content as a version
	$previous_versions[$ccg_version . '-' . $tp_version] = array(
		'date' => current_time('mysql'),
		'content' => $content,
		'blocks' => $blocks
	);
	
	$version_notes = isset($_POST['version_notes']) ? sanitize_textarea_field($_POST['version_notes']) : '';
	
	// And when storing the version data:
	$previous_versions[$ccg_version . '-' . $tp_version] = array(
		'date' => current_time('mysql'),
		'content' => $content,
		'blocks' => $blocks,
		'notes' => $version_notes
	);
	
	// Store versions
	update_post_meta($post_id, 'versions', $previous_versions);
	
	// Update version numbers
	update_post_meta($post_id, 'ccg_version', $new_ccg_version);
	update_post_meta($post_id, 'tp_version', $new_tp_version);
	
	// Calculate changes if we have a previous version
	if (count($previous_versions) > 1) {
		// Get keys of versions array
		$version_keys = array_keys($previous_versions);
		
		// Get previous version
		$previous_version_key = $version_keys[count($version_keys) - 2];
		$previous_version = $previous_versions[$previous_version_key];
		
		// Compare blocks
		$changes = tm_compare_blocks($previous_version['blocks'], $blocks);
		
		// Store changes
		$changelog = get_post_meta($post_id, 'changelog', true);
		if (!is_array($changelog)) {
			$changelog = array();
		}
		
		$changelog[$new_ccg_version . '-' . $new_tp_version] = array(
			'date' => current_time('mysql'),
			'type' => $version_type,
			'changes' => $changes
		);
		
		update_post_meta($post_id, 'changelog', $changelog);
	}
	
	do_action('tm_version_created', $post_id, $new_ccg_version, $new_tp_version);
	
	return array(
		'ccg_version' => $new_ccg_version,
		'tp_version' => $new_tp_version
	);
}

/**
 * Increment version number
 */
function tm_increment_version($version, $type = 'minor') {
	$parts = explode('.', $version);
	
	// Ensure we have a valid semver format
	if (count($parts) !== 3) {
		return '1.0.0';
	}
	
	$major = (int) $parts[0];
	$minor = (int) $parts[1];
	$patch = (int) $parts[2];
	
	switch ($type) {
		case 'major':
			$major++;
			$minor = 0;
			$patch = 0;
			break;
		case 'minor':
			$minor++;
			$patch = 0;
			break;
		case 'patch':
		case 'hotfix':
			$patch++;
			break;
		case 'basic':
			// No version change
			break;
	}
	
	return $major . '.' . $minor . '.' . $patch;
}

/**
 * Rollback to a specific version
 */
function tm_rollback_to_version($post_id, $version) {
	// Get versions
	$versions = get_post_meta($post_id, 'versions', true);
	
	// Check if version exists
	if (!isset($versions[$version])) {
		return false;
	}
	
	// Get version content
	$version_data = $versions[$version];
	$content = $version_data['content'];
	
	// Update post with version content
	$post = array(
		'ID' => $post_id,
		'post_content' => $content
	);
	
	wp_update_post($post);
	
	// Get version numbers
	$version_parts = explode('-', $version);
	$ccg_version = $version_parts[0];
	$tp_version = $version_parts[1];
	
	// Update version meta
	update_post_meta($post_id, 'ccg_version', $ccg_version);
	update_post_meta($post_id, 'tp_version', $tp_version);
	
	do_action('tm_version_rolled_back', $post_id, $version);
	
	return true;
}

/**
 * Register a custom meta box below the Gutenberg editor
 */
function tm_add_below_editor_meta_box() {
	add_meta_box(
		'tm-version-history-panel',
		__('Version History and Control', 'test-method-versioning'),
		'tm_render_version_panel',
		'test_method',
		'normal',
		'high'
	);
}
add_action('add_meta_boxes', 'tm_add_below_editor_meta_box');

/**
 * Render the version panel content
 */
function tm_render_version_panel($post) {
	// Get version history
	$versions = get_post_meta($post->ID, 'versions', true);
	if (!is_array($versions)) {
		$versions = array();
	}
	
	// Get current versions
	$ccg_version = get_post_meta($post->ID, 'ccg_version', true) ?: '1.0.0';
	$tp_version = get_post_meta($post->ID, 'tp_version', true) ?: '1.0.0';
	
	// Get changelog
	$changelog = get_post_meta($post->ID, 'changelog', true);
	if (!is_array($changelog)) {
		$changelog = array();
	}
	
	?>
	<div class="tm-version-panel">
		<div class="tm-version-panel-header">
			<h2><?php _e('Version Control', 'test-method-versioning'); ?></h2>
			<div class="tm-current-version">
				<span><?php _e('Current CCG Version:', 'test-method-versioning'); ?> <strong><?php echo esc_html($ccg_version); ?></strong></span>
				<span><?php _e('Current TP Version:', 'test-method-versioning'); ?> <strong><?php echo esc_html($tp_version); ?></strong></span>
			</div>
		</div>
		
		<div class="tm-version-panel-actions">
			<div class="tm-create-version-form">
				<h3><?php _e('Create New Version', 'test-method-versioning'); ?></h3>
				<div class="tm-version-form-row">
					<label for="tm_version_type_panel"><?php _e('Version Type:', 'test-method-versioning'); ?></label>
					<select name="tm_version_type_panel" id="tm_version_type_panel">
						<option value="minor"><?php _e('Minor Version (1.x.0 → 1.x+1.0)', 'test-method-versioning'); ?></option>
						<option value="major"><?php _e('Major Version (x.0.0 → x+1.0.0)', 'test-method-versioning'); ?></option>
						<option value="patch"><?php _e('Patch (x.y.z → x.y.z+1)', 'test-method-versioning'); ?></option>
					</select>
				</div>
				
				<div class="tm-version-form-row">
					<label for="tm_version_notes"><?php _e('Version Notes:', 'test-method-versioning'); ?></label>
					<textarea name="tm_version_notes" id="tm_version_notes" rows="3"></textarea>
				</div>
				
				<div class="tm-version-form-row">
					<button type="button" class="button button-primary" id="tm-create-version-panel"><?php _e('Create New Version', 'test-method-versioning'); ?></button>
				</div>
			</div>
			
			<div class="tm-rollback-version-form">
				<h3><?php _e('Rollback to Previous Version', 'test-method-versioning'); ?></h3>
				
				<?php if (empty($versions)) : ?>
					<p><?php _e('No previous versions available.', 'test-method-versioning'); ?></p>
				<?php else : ?>
					<div class="tm-version-form-row">
						<label for="tm_rollback_version_panel"><?php _e('Select Version:', 'test-method-versioning'); ?></label>
						<select name="tm_rollback_version_panel" id="tm_rollback_version_panel">
							<option value=""><?php _e('-- Select Version --', 'test-method-versioning'); ?></option>
							<?php foreach (array_keys($versions) as $version_key) : ?>
								<option value="<?php echo esc_attr($version_key); ?>"><?php echo esc_html($version_key); ?> (<?php echo date_i18n(get_option('date_format'), strtotime($versions[$version_key]['date'])); ?>)</option>
							<?php endforeach; ?>
						</select>
					</div>
					
					<div class="tm-version-form-row">
						<button type="button" class="button" id="tm-rollback-version-panel"><?php _e('Rollback to Selected Version', 'test-method-versioning'); ?></button>
					</div>
				<?php endif; ?>
			</div>
		</div>
		
		<div class="tm-version-panel-history">
			<h3><?php _e('Version History', 'test-method-versioning'); ?></h3>
			
			<?php if (empty($versions)) : ?>
				<p><?php _e('No version history available.', 'test-method-versioning'); ?></p>
			<?php else : ?>
				<table class="tm-version-history-table widefat">
					<thead>
						<tr>
							<th><?php _e('Version', 'test-method-versioning'); ?></th>
							<th><?php _e('Date', 'test-method-versioning'); ?></th>
							<th><?php _e('Changes', 'test-method-versioning'); ?></th>
							<th><?php _e('Actions', 'test-method-versioning'); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php 
						// Sort versions by date (newest first)
						$sorted_versions = array();
						foreach ($versions as $key => $data) {
							$sorted_versions[$key] = strtotime($data['date']);
						}
						arsort($sorted_versions);
						
						foreach ($sorted_versions as $version_key => $timestamp) :
							$version_data = $versions[$version_key];
							$version_parts = explode('-', $version_key);
							$ccg = $version_parts[0];
							$tp = isset($version_parts[1]) ? $version_parts[1] : '';
							
							// Get changelog for this version
							$version_changelog = isset($changelog[$version_key]) ? $changelog[$version_key] : array();
						?>
							<tr>
								<td>
									<div class="tm-version-number">
										<div><strong><?php _e('CCG:', 'test-method-versioning'); ?></strong> <?php echo esc_html($ccg); ?></div>
										<div><strong><?php _e('TP:', 'test-method-versioning'); ?></strong> <?php echo esc_html($tp); ?></div>
									</div>
								</td>
								<td><?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($version_data['date'])); ?></td>
								<td>
									<?php if (!empty($version_changelog) && isset($version_changelog['changes'])) : ?>
										<div class="tm-version-changelog">
											<?php foreach ($version_changelog['changes'] as $change) : ?>
												<div class="tm-change-item tm-change-<?php echo esc_attr($change['type']); ?>">
													<span class="tm-change-type"><?php echo esc_html(ucfirst($change['type'])); ?></span>
													<span class="tm-change-text"><?php echo esc_html($change['message']); ?></span>
												</div>
											<?php endforeach; ?>
										</div>
									<?php else : ?>
										<em><?php _e('No detailed changelog available', 'test-method-versioning'); ?></em>
									<?php endif; ?>
								</td>
								<td>
									<button type="button" class="button tm-view-version" data-version="<?php echo esc_attr($version_key); ?>"><?php _e('View', 'test-method-versioning'); ?></button>
									<button type="button" class="button tm-restore-version" data-version="<?php echo esc_attr($version_key); ?>"><?php _e('Restore', 'test-method-versioning'); ?></button>
									<?php if ($version_key !== $ccg_version . '-' . $tp_version) : ?>
										<button type="button" class="button tm-compare-version" data-version="<?php echo esc_attr($version_key); ?>"><?php _e('Compare', 'test-method-versioning'); ?></button>
									<?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
	</div>
	
	<!-- Version comparison modal -->
	<div id="tm-version-compare-modal" class="tm-modal">
		<div class="tm-modal-content">
			<span class="tm-modal-close">&times;</span>
			<h2><?php _e('Version Comparison', 'test-method-versioning'); ?></h2>
			<div id="tm-comparison-content"></div>
		</div>
	</div>
	
	<script>
	jQuery(document).ready(function($) {
		// Create version button click handler
		$('#tm-create-version-panel').on('click', function() {
			if (!confirm('<?php _e('Are you sure you want to create a new version? This will save the current content.', 'test-method-versioning'); ?>')) {
				return;
			}
			
			var data = {
				action: 'tm_create_version',
				post_id: <?php echo $post->ID; ?>,
				version_type: $('#tm_version_type_panel').val(),
				version_notes: $('#tm_version_notes').val(),
				nonce: '<?php echo wp_create_nonce('tm_version_nonce'); ?>'
			};
			
			$.post(ajaxurl, data, function(response) {
				if (response.success) {
					alert('<?php _e('New version created successfully!', 'test-method-versioning'); ?>');
					location.reload();
				} else {
					alert('<?php _e('Error creating version.', 'test-method-versioning'); ?>');
				}
			});
		});
		
		// Rollback version button click handler
		$('#tm-rollback-version-panel').on('click', function() {
			var version = $('#tm_rollback_version_panel').val();
			
			if (!version) {
				alert('<?php _e('Please select a version to rollback to.', 'test-method-versioning'); ?>');
				return;
			}
			
			if (!confirm('<?php _e('Are you sure you want to rollback to this version? Current unsaved changes will be lost.', 'test-method-versioning'); ?>')) {
				return;
			}
			
			var data = {
				action: 'tm_rollback_version',
				post_id: <?php echo $post->ID; ?>,
				version: version,
				nonce: '<?php echo wp_create_nonce('tm_version_nonce'); ?>'
			};
			
			$.post(ajaxurl, data, function(response) {
				if (response.success) {
					alert('<?php _e('Rolled back to selected version successfully!', 'test-method-versioning'); ?>');
					location.reload();
				} else {
					alert('<?php _e('Error rolling back to version.', 'test-method-versioning'); ?>');
				}
			});
		});
		
		// View version button click handler
		$('.tm-view-version').on('click', function() {
			var version = $(this).data('version');
			
			var data = {
				action: 'tm_view_version',
				post_id: <?php echo $post->ID; ?>,
				version: version,
				nonce: '<?php echo wp_create_nonce('tm_version_nonce'); ?>'
			};
			
			$.post(ajaxurl, data, function(response) {
				if (response.success) {
					var win = window.open('', '_blank');
					win.document.write('<html><head><title>Version ' + version + '</title>');
					win.document.write('<style>body { font-family: sans-serif; padding: 20px; max-width: 800px; margin: 0 auto; }</style>');
					win.document.write('</head><body>');
					win.document.write('<h1>Version ' + version + '</h1>');
					win.document.write(response.data.content);
					win.document.write('</body></html>');
					win.document.close();
				} else {
					alert('<?php _e('Error viewing version.', 'test-method-versioning'); ?>');
				}
			});
		});
		
		// Restore version button click handler
		$('.tm-restore-version').on('click', function() {
			var version = $(this).data('version');
			
			if (!confirm('<?php _e('Are you sure you want to restore this version? Current unsaved changes will be lost.', 'test-method-versioning'); ?>')) {
				return;
			}
			
			var data = {
				action: 'tm_rollback_version',
				post_id: <?php echo $post->ID; ?>,
				version: version,
				nonce: '<?php echo wp_create_nonce('tm_version_nonce'); ?>'
			};
			
			$.post(ajaxurl, data, function(response) {
				if (response.success) {
					alert('<?php _e('Restored to selected version successfully!', 'test-method-versioning'); ?>');
					location.reload();
				} else {
					alert('<?php _e('Error restoring version.', 'test-method-versioning'); ?>');
				}
			});
		});
		
		// Compare version button click handler
		$('.tm-compare-version').on('click', function() {
			var version = $(this).data('version');
			
			var data = {
				action: 'tm_compare_versions',
				post_id: <?php echo $post->ID; ?>,
				version1: version,
				version2: '<?php echo esc_js($ccg_version . '-' . $tp_version); ?>',
				nonce: '<?php echo wp_create_nonce('tm_version_nonce'); ?>'
			};
			
			$.post(ajaxurl, data, function(response) {
				if (response.success) {
					$('#tm-comparison-content').html(response.data.comparison);
					$('#tm-version-compare-modal').show();
				} else {
					alert('<?php _e('Error comparing versions.', 'test-method-versioning'); ?>');
				}
			});
		});
		
		// Close modal when clicking the close button
		$('.tm-modal-close').on('click', function() {
			$('.tm-modal').hide();
		});
		
		// Close modal when clicking outside of it
		$(window).on('click', function(event) {
			if ($(event.target).hasClass('tm-modal')) {
				$('.tm-modal').hide();
			}
		});
	});
	</script>
	
	<style>
	.tm-version-panel {
		margin: 20px 0;
		background: #fff;
		border: 1px solid #e5e5e5;
		box-shadow: 0 1px 1px rgba(0,0,0,0.04);
	}
	
	.tm-version-panel-header {
		padding: 15px;
		border-bottom: 1px solid #eee;
		display: flex;
		justify-content: space-between;
		align-items: center;
	}
	
	.tm-version-panel-header h2 {
		margin: 0;
	}
	
	.tm-current-version {
		display: flex;
		gap: 20px;
	}
	
	.tm-version-panel-actions {
		padding: 15px;
		border-bottom: 1px solid #eee;
		display: flex;
		gap: 30px;
	}
	
	.tm-create-version-form,
	.tm-rollback-version-form {
		flex: 1;
	}
	
	.tm-version-form-row {
		margin-bottom: 10px;
	}
	
	.tm-version-form-row label {
		display: block;
		margin-bottom: 5px;
		font-weight: 600;
	}
	
	.tm-version-form-row select,
	.tm-version-form-row textarea {
		width: 100%;
	}
	
	.tm-version-panel-history {
		padding: 15px;
	}
	
	.tm-version-history-table {
		width: 100%;
		border-collapse: collapse;
	}
	
	.tm-version-history-table th,
	.tm-version-history-table td {
		padding: 10px;
		text-align: left;
		vertical-align: top;
		border-bottom: 1px solid #eee;
	}
	
	.tm-version-number {
		display: flex;
		flex-direction: column;
		gap: 5px;
	}
	
	.tm-version-changelog {
		display: flex;
		flex-direction: column;
		gap: 5px;
	}
	
	.tm-change-item {
		display: flex;
		gap: 5px;
	}
	
	.tm-change-type {
		font-weight: 600;
	}
	
	.tm-change-added {
		color: #4caf50;
	}
	
	.tm-change-modified {
		color: #2196f3;
	}
	
	.tm-change-removed {
		color: #f44336;
	}
	
	/* Modal styles */
	.tm-modal {
		display: none;
		position: fixed;
		z-index: 1000;
		left: 0;
		top: 0;
		width: 100%;
		height: 100%;
		overflow: auto;
		background-color: rgba(0,0,0,0.4);
	}
	
	.tm-modal-content {
		position: relative;
		background-color: #fefefe;
		margin: 10% auto;
		padding: 20px;
		border: 1px solid #888;
		width: 80%;
		max-width: 800px;
		box-shadow: 0 4px 8px 0 rgba(0,0,0,0.2);
	}
	
	.tm-modal-close {
		color: #aaa;
		float: right;
		font-size: 28px;
		font-weight: bold;
		cursor: pointer;
	}
	
	.tm-modal-close:hover,
	.tm-modal-close:focus {
		color: black;
		text-decoration: none;
		cursor: pointer;
	}
	
	#tm-comparison-content {
		margin-top: 20px;
		max-height: 500px;
		overflow-y: auto;
	}
	
	#tm-comparison-content ins {
		background-color: #d4edda;
		text-decoration: none;
	}
	
	#tm-comparison-content del {
		background-color: #f8d7da;
		text-decoration: none;
	}
	</style>
	<?php
}

/**
 * Add version management meta box to sidebar
 */
function tm_add_version_management_meta_box() {
	add_meta_box(
		'astp_version_manager',
		__('Version Management', 'test-method-versioning'),
		'tm_render_version_management_meta_box',
		'test_method',
		'side',
		'high'
	);
}
add_action('add_meta_boxes', 'tm_add_version_management_meta_box');

/**
 * Render version management meta box
 */
function tm_render_version_management_meta_box($post) {
	wp_nonce_field('astp_version_action', 'astp_version_nonce');
	
	// Get CCG version data
	$ccg_published_version = get_post_meta($post->ID, 'ccg_version', true) ?: '1.0.0';
	$ccg_published_date = get_post_meta($post->ID, 'ccg_published_date', true);
	$ccg_version_post_id = get_post_meta($post->ID, 'ccg_version_post_id', true);
	$ccg_in_development = get_post_meta($post->ID, 'ccg_in_development', true);
	
	// Get TP version data
	$tp_published_version = get_post_meta($post->ID, 'tp_version', true) ?: '1.0.0';
	$tp_published_date = get_post_meta($post->ID, 'tp_published_date', true);
	$tp_version_post_id = get_post_meta($post->ID, 'tp_version_post_id', true);
	$tp_in_development = get_post_meta($post->ID, 'tp_in_development', true);
	
	?>
	<div class="astp-version-controls">
		<!-- CCG Section -->
		<div class="astp-version-section astp-ccg-section">
			<h3><?php _e('Certification Companion Guide (CCG)', 'test-method-versioning'); ?></h3>
			
			<div class="astp-published-version">
				<h4><?php _e('Published Version', 'test-method-versioning'); ?></h4>
				
				<?php if ($ccg_published_version && $ccg_version_post_id) : ?>
					<p><strong><?php _e('Current:', 'test-method-versioning'); ?></strong> 
						<span class="astp-version-status astp-status-published"><?php echo esc_html($ccg_published_version); ?></span>
					</p>
					
					<?php if ($ccg_published_date) : ?>
						<p><strong><?php _e('Released:', 'test-method-versioning'); ?></strong> 
							<?php echo esc_html(date_i18n(get_option('date_format'), strtotime($ccg_published_date))); ?>
						</p>
					<?php endif; ?>
					
					<p>
						<a href="<?php echo esc_url(get_edit_post_link($ccg_version_post_id)); ?>" class="button astp-view-version" data-version-id="<?php echo esc_attr($ccg_version_post_id); ?>">
							<?php _e('View Details', 'test-method-versioning'); ?>
						</a>
					</p>
					
					<p>
						<a href="#" class="button astp-create-hotfix" data-version-id="<?php echo esc_attr($ccg_version_post_id); ?>" data-post-id="<?php echo esc_attr($post->ID); ?>" data-document-type="ccg">
							<?php _e('Create Hotfix', 'test-method-versioning'); ?>
						</a>
					</p>
				<?php else : ?>
					<p><em><?php _e('No published version yet.', 'test-method-versioning'); ?></em></p>
				<?php endif; ?>
			</div>
			
			<div class="astp-development-version">
				<h4><?php _e('Development Version', 'test-method-versioning'); ?></h4>
				
				<?php if ($ccg_in_development) : ?>
					<p>
						<a href="<?php echo esc_url(get_edit_post_link($ccg_in_development)); ?>" class="button">
							<?php _e('Edit Development Version', 'test-method-versioning'); ?>
						</a>
					</p>
				<?php else : ?>
					<p><em><?php _e('No version in development.', 'test-method-versioning'); ?></em></p>
					
					<hr>
					
					<h4><?php _e('Create New Version', 'test-method-versioning'); ?></h4>
					<p><?php _e('Select version type:', 'test-method-versioning'); ?></p>
					
					<select id="astp-ccg-version-type" class="astp-version-type">
						<option value="minor"><?php _e('Minor Update', 'test-method-versioning'); ?></option>
						<option value="major"><?php _e('Major Release', 'test-method-versioning'); ?></option>
					</select>
					
					<p>
						<a href="#" class="button button-primary astp-create-version" data-post-id="<?php echo esc_attr($post->ID); ?>" data-document-type="ccg">
							<?php _e('Create New Version', 'test-method-versioning'); ?>
						</a>
					</p>
				<?php endif; ?>
			</div>
			
			<hr>
			
			<p>
				<a href="#" class="button astp-view-version-history" data-post-id="<?php echo esc_attr($post->ID); ?>" data-document-type="ccg">
					<?php _e('View CCG Version History', 'test-method-versioning'); ?>
				</a>
			</p>
		</div>
		
		<hr class="astp-section-divider">
		
		<!-- TP Section -->
		<div class="astp-version-section astp-tp-section">
			<h3><?php _e('Test Procedure (TP)', 'test-method-versioning'); ?></h3>
			
			<div class="astp-published-version">
				<h4><?php _e('Published Version', 'test-method-versioning'); ?></h4>
				
				<?php if ($tp_published_version && $tp_version_post_id) : ?>
					<p><strong><?php _e('Current:', 'test-method-versioning'); ?></strong> 
						<span class="astp-version-status astp-status-published"><?php echo esc_html($tp_published_version); ?></span>
					</p>
					
					<?php if ($tp_published_date) : ?>
						<p><strong><?php _e('Released:', 'test-method-versioning'); ?></strong> 
							<?php echo esc_html(date_i18n(get_option('date_format'), strtotime($tp_published_date))); ?>
						</p>
					<?php endif; ?>
					
					<p>
						<a href="<?php echo esc_url(get_edit_post_link($tp_version_post_id)); ?>" class="button astp-view-version" data-version-id="<?php echo esc_attr($tp_version_post_id); ?>">
							<?php _e('View Details', 'test-method-versioning'); ?>
						</a>
					</p>
					
					<p>
						<a href="#" class="button astp-create-hotfix" data-version-id="<?php echo esc_attr($tp_version_post_id); ?>" data-post-id="<?php echo esc_attr($post->ID); ?>" data-document-type="tp">
							<?php _e('Create Hotfix', 'test-method-versioning'); ?>
						</a>
					</p>
				<?php else : ?>
					<p><em><?php _e('No published version yet.', 'test-method-versioning'); ?></em></p>
				<?php endif; ?>
			</div>
			
			<div class="astp-development-version">
				<h4><?php _e('Development Version', 'test-method-versioning'); ?></h4>
				
				<?php if ($tp_in_development) : ?>
					<p>
						<a href="<?php echo esc_url(get_edit_post_link($tp_in_development)); ?>" class="button">
							<?php _e('Edit Development Version', 'test-method-versioning'); ?>
						</a>
					</p>
				<?php else : ?>
					<p><em><?php _e('No version in development.', 'test-method-versioning'); ?></em></p>
					
					<hr>
					
					<h4><?php _e('Create New Version', 'test-method-versioning'); ?></h4>
					<p><?php _e('Select version type:', 'test-method-versioning'); ?></p>
					
					<select id="astp-tp-version-type" class="astp-version-type">
						<option value="minor"><?php _e('Minor Update', 'test-method-versioning'); ?></option>
						<option value="major"><?php _e('Major Release', 'test-method-versioning'); ?></option>
					</select>
					
					<p>
						<a href="#" class="button button-primary astp-create-version" data-post-id="<?php echo esc_attr($post->ID); ?>" data-document-type="tp">
							<?php _e('Create New Version', 'test-method-versioning'); ?>
						</a>
					</p>
				<?php endif; ?>
			</div>
			
			<hr>
			
			<p>
				<a href="#" class="button astp-view-version-history" data-post-id="<?php echo esc_attr($post->ID); ?>" data-document-type="tp">
					<?php _e('View TP Version History', 'test-method-versioning'); ?>
				</a>
			</p>
		</div>
	</div>
	
	<style>
		.astp-version-section {
			margin-bottom: 20px;
			padding-bottom: 10px;
		}
		.astp-section-divider {
			margin: 20px 0;
			border-top: 1px solid #ddd;
		}
		.astp-ccg-section h3 {
			color: #2271b1;
		}
		.astp-tp-section h3 {
			color: #3c6e21;
		}
		.astp-legacy-section {
			opacity: 0.7;
		}
		.astp-legacy-section h3 {
			color: #888;
			font-size: 14px;
		}
	</style>
	<?php
}

/**
 * Compare two sets of blocks to identify changes
 *
 * @param array $old_blocks Previous version blocks
 * @param array $new_blocks Current blocks
 * @return array Changes found between block versions
 */
function tm_compare_blocks($old_blocks, $new_blocks) {
	$changes = array();
	
	// Extract block IDs for comparison
	$old_block_ids = array();
	foreach ($old_blocks as $index => $block) {
		if (isset($block['attrs']['blockId'])) {
			$old_block_ids[$block['attrs']['blockId']] = $index;
		} else {
			// For blocks without ID, use index as pseudo-ID
			$old_block_ids['index_' . $index] = $index;
		}
	}
	
	$new_block_ids = array();
	foreach ($new_blocks as $index => $block) {
		if (isset($block['attrs']['blockId'])) {
			$new_block_ids[$block['attrs']['blockId']] = $index;
		} else {
			// For blocks without ID, use index as pseudo-ID
			$new_block_ids['index_' . $index] = $index;
		}
	}
	
	// Find added blocks (in new but not in old)
	foreach ($new_block_ids as $id => $index) {
		if (!isset($old_block_ids[$id]) && substr($id, 0, 6) !== 'index_') {
			$changes[] = array(
				'type' => 'added',
				'block_id' => $id,
				'message' => sprintf(__('Added block %s', 'test-method-versioning'), $id)
			);
		}
	}
	
	// Find removed blocks (in old but not in new)
	foreach ($old_block_ids as $id => $index) {
		if (!isset($new_block_ids[$id]) && substr($id, 0, 6) !== 'index_') {
			$changes[] = array(
				'type' => 'removed',
				'block_id' => $id,
				'message' => sprintf(__('Removed block %s', 'test-method-versioning'), $id)
			);
		}
	}
	
	// Find modified blocks (in both but content changed)
	foreach ($old_block_ids as $id => $old_index) {
		if (isset($new_block_ids[$id])) {
			$new_index = $new_block_ids[$id];
			
			// Compare block content
			$old_content = wp_json_encode($old_blocks[$old_index]);
			$new_content = wp_json_encode($new_blocks[$new_index]);
			
			if ($old_content !== $new_content) {
				$changes[] = array(
					'type' => 'modified',
					'block_id' => $id,
					'message' => sprintf(__('Modified block %s', 'test-method-versioning'), $id)
				);
			}
		}
	}
	
	return $changes;
}


<?php
/**
 * Test Method Dashboard
 */

/**
 * Add dashboard menu
 */
function tm_add_dashboard_menu() {
	add_menu_page(
		__('Test Method Dashboard', 'test-method-versioning'),
		__('Test Methods', 'test-method-versioning'),
		'edit_posts',
		'test-method-dashboard',
		'tm_render_dashboard',
		'dashicons-clipboard',
		30
	);
}
add_action('admin_menu', 'tm_add_dashboard_menu');

/**
 * Render dashboard page
 */
function tm_render_dashboard() {
	// Check user permissions
	if (!current_user_can('edit_posts')) {
		wp_die(__('You do not have sufficient permissions to access this page.', 'test-method-versioning'));
	}
	
	// Get current user roles
	$current_user = wp_get_current_user();
	$user_roles = $current_user->roles;
	
	// Set flags based on user roles
	$can_approve = in_array('tp_approver', $user_roles) || in_array('tp_admin', $user_roles) || in_array('administrator', $user_roles);
	$can_publish = in_array('tp_admin', $user_roles) || in_array('administrator', $user_roles);
	
	// Get test methods
	$args = array(
		'post_type' => 'test_method',
		'posts_per_page' => -1,
		'orderby' => 'modified',
		'order' => 'DESC'
	);
	
	// Filter by status if provided
	if (isset($_GET['status']) && !empty($_GET['status'])) {
		if ($_GET['status'] === 'draft') {
			$args['post_status'] = 'draft';
		} elseif ($_GET['status'] === 'published') {
			$args['post_status'] = 'publish';
		} elseif (in_array($_GET['status'], array('submitted', 'approved', 'rejected', 'revision'))) {
			$args['meta_query'] = array(
				array(
					'key' => 'workflow_status',
					'value' => sanitize_text_field($_GET['status']),
					'compare' => '='
				)
			);
		}
	}
	
	$test_methods = get_posts($args);
	
	// Start output
	?>
	<div class="wrap tm-dashboard">
		<h1><?php _e('Test Method Dashboard', 'test-method-versioning'); ?></h1>
		
		<?php
		// Status filters
		$statuses = array(
			'all' => __('All Test Methods', 'test-method-versioning'),
			'draft' => __('Drafts', 'test-method-versioning'),
			'submitted' => __('Submitted for Review', 'test-method-versioning'),
			'approved' => __('Approved', 'test-method-versioning'),
			'rejected' => __('Rejected', 'test-method-versioning'),
			'published' => __('Published', 'test-method-versioning'),
			'revision' => __('In Revision', 'test-method-versioning')
		);
		
		$current_status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : 'all';
		?>
		
		<div class="tm-status-filters">
			<ul>
				<?php foreach ($statuses as $status => $label) : ?>
					<li class="<?php echo $status === $current_status ? 'current' : ''; ?>">
						<a href="<?php echo add_query_arg('status', $status); ?>">
							<?php echo $label; ?>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
		
		<?php if (empty($test_methods)) : ?>
			<div class="tm-no-items">
				<p><?php _e('No test methods found.', 'test-method-versioning'); ?></p>
			</div>
		<?php else : ?>
			<table class="wp-list-table widefat fixed striped tm-posts-table">
				<thead>
					<tr>
						<th><?php _e('Title', 'test-method-versioning'); ?></th>
						<th><?php _e('Author', 'test-method-versioning'); ?></th>
						<th><?php _e('Status', 'test-method-versioning'); ?></th>
						<th><?php _e('Version', 'test-method-versioning'); ?></th>
						<th><?php _e('Last Modified', 'test-method-versioning'); ?></th>
						<th><?php _e('Approvals', 'test-method-versioning'); ?></th>
						<th><?php _e('Actions', 'test-method-versioning'); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($test_methods as $post) : 
						// Get workflow status
						$workflow_status = get_post_meta($post->ID, 'workflow_status', true);
						if (empty($workflow_status) && $post->post_status === 'draft') {
							$workflow_status = 'draft';
						} elseif (empty($workflow_status) && $post->post_status === 'publish') {
							$workflow_status = 'published';
						}
						
						// Get version
						$ccg_version = get_post_meta($post->ID, 'ccg_version', true) ?: '1.0.0';
						$tp_version = get_post_meta($post->ID, 'tp_version', true) ?: '1.0.0';
						$version = $ccg_version . '-' . $tp_version;
						
						// Get approvals
						$approvals = get_post_meta($post->ID, 'approvals', true);
						$approvals_count = 0;
						
						if (is_array($approvals)) {
							foreach ($approvals as $approval) {
								if ($approval['status'] === 'approved') {
									$approvals_count++;
								}
							}
						}
						
						// Get author
						$author = get_userdata($post->post_author);
						$author_name = $author ? $author->display_name : __('Unknown', 'test-method-versioning');
						
						// Last modified date
						$modified_date = get_the_modified_date('Y-m-d H:i:s', $post);
					?>
						<tr>
							<td>
								<strong>
									<a href="<?php echo get_edit_post_link($post->ID); ?>">
										<?php echo esc_html($post->post_title); ?>
									</a>
								</strong>
							</td>
							<td><?php echo esc_html($author_name); ?></td>
							<td>
								<?php
								// Display status with badge
								$status_class = 'tm-status-' . $workflow_status;
								$status_label = '';
								
								switch ($workflow_status) {
									case 'draft':
										$status_label = __('Draft', 'test-method-versioning');
										break;
									case 'submitted':
										$status_label = __('Submitted for Review', 'test-method-versioning');
										break;
									case 'approved':
										$status_label = __('Approved', 'test-method-versioning');
										break;
									case 'rejected':
										$status_label = __('Rejected', 'test-method-versioning');
										break;
									case 'published':
										$status_label = __('Published', 'test-method-versioning');
										break;
									case 'revision':
										$status_label = __('In Revision', 'test-method-versioning');
										break;
									default:
										$status_label = $post->post_status;
										break;
								}
								?>
								<span class="tm-status-badge <?php echo $status_class; ?>">
									<?php echo $status_label; ?>
								</span>
							</td>
							<td><?php echo esc_html($version); ?></td>
							<td><?php echo esc_html($modified_date); ?></td>
							<td>
								<?php echo $approvals_count; ?> / 2
								<?php if ($approvals_count > 0 && $can_approve) : ?>
									<a href="#" class="tm-view-approvals" data-post-id="<?php echo $post->ID; ?>">
										<?php _e('View', 'test-method-versioning'); ?>
									</a>
								<?php endif; ?>
							</td>
							<td class="tm-actions">
								<a href="<?php echo get_edit_post_link($post->ID); ?>" class="button">
									<?php _e('Edit', 'test-method-versioning'); ?>
								</a>
								
								<?php if ($workflow_status === 'submitted' && $can_approve) : ?>
									<button class="button tm-action-approve" data-post-id="<?php echo $post->ID; ?>">
										<?php _e('Approve', 'test-method-versioning'); ?>
									</button>
									<button class="button tm-action-reject" data-post-id="<?php echo $post->ID; ?>">
										<?php _e('Reject', 'test-method-versioning'); ?>
									</button>
								<?php endif; ?>
								
								<?php if ($workflow_status === 'approved' && $can_publish) : ?>
									<button class="button button-primary tm-action-publish" data-post-id="<?php echo $post->ID; ?>">
										<?php _e('Publish', 'test-method-versioning'); ?>
									</button>
								<?php endif; ?>
								
								<?php if ($workflow_status === 'published' && $can_publish) : ?>
									<button class="button tm-action-unlock" data-post-id="<?php echo $post->ID; ?>">
										<?php _e('Unlock', 'test-method-versioning'); ?>
									</button>
								<?php endif; ?>
								
								<a href="<?php echo get_permalink($post->ID); ?>" class="button" target="_blank">
									<?php _e('View', 'test-method-versioning'); ?>
								</a>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>
		
		<!-- Modal for approvals -->
		<div id="tm-approvals-modal" class="tm-modal" style="display: none;">
			<div class="tm-modal-content">
				<span class="tm-modal-close">&times;</span>
				<h2><?php _e('Approval History', 'test-method-versioning'); ?></h2>
				<div id="tm-approvals-content"></div>
			</div>
		</div>
		
		<!-- Modal for actions -->
		<div id="tm-action-modal" class="tm-modal" style="display: none;">
			<div class="tm-modal-content">
				<span class="tm-modal-close">&times;</span>
				<h2 id="tm-action-title"></h2>
				<div id="tm-action-content">
					<p id="tm-action-description"></p>
					
					<div id="tm-version-type-container" style="display: none;">
						<label for="tm-version-type"><?php _e('Version Type:', 'test-method-versioning'); ?></label>
						<select id="tm-version-type">
							<option value="basic"><?php _e('Basic (No Version Change)', 'test-method-versioning'); ?></option>
							<option value="minor" selected><?php _e('Minor Version (x.Y.0)', 'test-method-versioning'); ?></option>
							<option value="major"><?php _e('Major Version (X.0.0)', 'test-method-versioning'); ?></option>
							<option value="hotfix"><?php _e('Hotfix (x.y.Z)', 'test-method-versioning'); ?></option>
						</select>
					</div>
					
					<div id="tm-comment-container" style="display: none;">
						<label for="tm-comment"><?php _e('Comment:', 'test-method-versioning'); ?></label>
						<textarea id="tm-comment" rows="4"></textarea>
					</div>
					
					<div class="tm-action-buttons">
						<button id="tm-action-confirm" class="button button-primary"></button>
						<button id="tm-action-cancel" class="button"><?php _e('Cancel', 'test-method-versioning'); ?></button>
					</div>
				</div>
			</div>
		</div>
	</div>
	<?php
}
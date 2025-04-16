<?php
/**
 * Register and handle post meta fields
 */

/**
 * Register post meta for test_method post type
 */
function tm_register_post_meta() {
	// Version meta
	register_post_meta('test_method', 'ccg_version', [
		'show_in_rest' => true,
		'single' => true,
		'type' => 'string',
		'default' => '1.0.0',
		'description' => 'CCG version number',
	]);
	
	register_post_meta('test_method', 'tp_version', [
		'show_in_rest' => true,
		'single' => true,
		'type' => 'string',
		'default' => '1.0.0',
		'description' => 'TP version number',
	]);
	
	// Workflow meta
	register_post_meta('test_method', 'workflow_status', [
		'show_in_rest' => true,
		'single' => true,
		'type' => 'string',
		'description' => 'Workflow status (draft, submitted, approved, rejected, published, revision)',
	]);
	
	register_post_meta('test_method', 'version_type', [
		'show_in_rest' => true,
		'single' => true,
		'type' => 'string',
		'description' => 'Type of version update (basic, minor, major, hotfix)',
	]);
	
	// Version history meta
	register_post_meta('test_method', 'versions', [
		'show_in_rest' => [
			'schema' => [
				'type' => 'object',
				'additionalProperties' => true,
			],
		],
		'single' => true,
		'type' => 'object',
		'description' => 'Version history data',
	]);
	
	// Changelog meta
	register_post_meta('test_method', 'changelog', [
		'show_in_rest' => [
			'schema' => [
				'type' => 'object',
				'additionalProperties' => true,
			],
		],
		'single' => true,
		'type' => 'object',
		'description' => 'Changelog data for each version',
	]);
	
	// Approvals meta
	register_post_meta('test_method', 'approvals', [
		'show_in_rest' => [
			'schema' => [
				'type' => 'array',
				'items' => [
					'type' => 'object',
					'properties' => [
						'user_id' => ['type' => 'integer'],
						'date' => ['type' => 'string'],
						'status' => ['type' => 'string'],
						'comments' => ['type' => 'string'],
						'version' => ['type' => 'string'],
					],
				],
			],
		],
		'single' => true,
		'type' => 'array',
		'description' => 'Approval history',
	]);
	
	// Document meta
	register_post_meta('test_method', 'documents', [
		'show_in_rest' => [
			'schema' => [
				'type' => 'object',
				'additionalProperties' => true,
			],
		],
		'single' => true,
		'type' => 'object',
		'description' => 'Document storage for each version',
	]);
	
	// GitHub URLs meta
	register_post_meta('test_method', 'github_urls', [
		'show_in_rest' => [
			'schema' => [
				'type' => 'object',
				'additionalProperties' => true,
			],
		],
		'single' => true,
		'type' => 'object',
		'description' => 'GitHub URLs for exported documents',
	]);
}
add_action('init', 'tm_register_post_meta');

/**
 * Add meta box for test_method post type
 */
function tm_add_meta_boxes() {
	add_meta_box(
		'tm_version_info',
		__('Version Information', 'test-method-versioning'),
		'tm_version_info_meta_box_callback',
		'test_method',
		'side',
		'high'
	);
}
add_action('add_meta_boxes', 'tm_add_meta_boxes');

/**
 * Render meta box content
 */
function tm_version_info_meta_box_callback($post) {
	// Get version data
	$ccg_version = get_post_meta($post->ID, 'ccg_version', true) ?: '1.0.0';
	$tp_version = get_post_meta($post->ID, 'tp_version', true) ?: '1.0.0';
	$workflow_status = get_post_meta($post->ID, 'workflow_status', true) ?: 'draft';
	
	// Add nonce for security
	wp_nonce_field('tm_version_info_nonce', 'tm_version_info_nonce');
	
	// Display version info
	?>
	<p>
		<strong><?php _e('CCG Version:', 'test-method-versioning'); ?></strong>
		<span><?php echo esc_html($ccg_version); ?></span>
	</p>
	
	<p>
		<strong><?php _e('TP Version:', 'test-method-versioning'); ?></strong>
		<span><?php echo esc_html($tp_version); ?></span>
	</p>
	
	<p>
		<strong><?php _e('Status:', 'test-method-versioning'); ?></strong>
		<span><?php echo esc_html($workflow_status); ?></span>
	</p>
	
	<?php
	// Display approval info if any
	$approvals = get_post_meta($post->ID, 'approvals', true);
	if (is_array($approvals) && !empty($approvals)) {
		$approved_count = 0;
		
		foreach ($approvals as $approval) {
			if ($approval['status'] === 'approved') {
				$approved_count++;
			}
		}
		
		?>
		<p>
			<strong><?php _e('Approvals:', 'test-method-versioning'); ?></strong>
			<span><?php echo $approved_count; ?> / 2</span>
		</p>
		<?php
	}
	
	// Display documents if available
	$documents = get_post_meta($post->ID, 'documents', true);
	if (is_array($documents) && !empty($documents)) {
		$version = $ccg_version . '-' . $tp_version;
		
		if (isset($documents[$version])) {
			$version_documents = $documents[$version];
			
			if (!empty($version_documents)) {
				?>
				<p><strong><?php _e('Documents:', 'test-method-versioning'); ?></strong></p>
				<ul>
					<?php
					if (isset($version_documents['word']) && $version_documents['word']) {
						$word_url = wp_get_attachment_url($version_documents['word']);
						if ($word_url) {
							?>
							<li>
								<a href="<?php echo esc_url($word_url); ?>" target="_blank">
									<?php _e('Word Document', 'test-method-versioning'); ?>
								</a>
							</li>
							<?php
						}
					}
					
					if (isset($version_documents['pdf']) && $version_documents['pdf']) {
						$pdf_url = wp_get_attachment_url($version_documents['pdf']);
						if ($pdf_url) {
							?>
							<li>
								<a href="<?php echo esc_url($pdf_url); ?>" target="_blank">
									<?php _e('PDF Document', 'test-method-versioning'); ?>
								</a>
							</li>
							<?php
						}
					}
					?>
				</ul>
				<?php
			}
		}
	}
}

/**
 * Add version column to admin list
 */
function tm_add_version_column($columns) {
	$new_columns = array();
	
	foreach ($columns as $key => $value) {
		$new_columns[$key] = $value;
		
		// Add version column after title
		if ($key === 'title') {
			$new_columns['version'] = __('Version', 'test-method-versioning');
			$new_columns['workflow_status'] = __('Status', 'test-method-versioning');
		}
	}
	
	return $new_columns;
}
add_filter('manage_test_method_posts_columns', 'tm_add_version_column');

/**
 * Display version column content
 */
function tm_display_version_column($column, $post_id) {
	if ($column === 'version') {
		$ccg_version = get_post_meta($post_id, 'ccg_version', true) ?: '1.0.0';
		$tp_version = get_post_meta($post_id, 'tp_version', true) ?: '1.0.0';
		
		echo esc_html($ccg_version . '-' . $tp_version);
	}
	
	if ($column === 'workflow_status') {
		$workflow_status = get_post_meta($post_id, 'workflow_status', true) ?: 'draft';
		
		$status_labels = array(
			'draft' => __('Draft', 'test-method-versioning'),
			'submitted' => __('Submitted for Review', 'test-method-versioning'),
			'approved' => __('Approved', 'test-method-versioning'),
			'rejected' => __('Rejected', 'test-method-versioning'),
			'published' => __('Published', 'test-method-versioning'),
			'revision' => __('In Revision', 'test-method-versioning'),
		);
		
		$label = isset($status_labels[$workflow_status]) ? $status_labels[$workflow_status] : $workflow_status;
		
		echo '<span class="tm-status-badge tm-status-' . esc_attr($workflow_status) . '">' . esc_html($label) . '</span>';
	}
}
add_action('manage_test_method_posts_custom_column', 'tm_display_version_column', 10, 2);

/**
 * Add sortable version column
 */
function tm_sortable_version_column($columns) {
	$columns['version'] = 'version';
	$columns['workflow_status'] = 'workflow_status';
	
	return $columns;
}
add_filter('manage_edit-test_method_sortable_columns', 'tm_sortable_version_column');

/**
 * Handle version column sorting
 */
function tm_version_column_orderby($query) {
	if (!is_admin() || !$query->is_main_query()) {
		return;
	}
	
	$orderby = $query->get('orderby');
	
	if ($orderby === 'version') {
		$query->set('meta_key', 'ccg_version');
		$query->set('orderby', 'meta_value');
	}
	
	if ($orderby === 'workflow_status') {
		$query->set('meta_key', 'workflow_status');
		$query->set('orderby', 'meta_value');
	}
}
add_action('pre_get_posts', 'tm_version_column_orderby');
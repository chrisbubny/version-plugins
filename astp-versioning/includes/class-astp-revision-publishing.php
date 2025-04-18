<?php
/**
 * Revision Publishing
 *
 * @package ASTP_Versioning
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
	exit;
}

/**
 * ASTP_Revision_Publishing Class
 */
class ASTP_Revision_Publishing {

	/**
	 * Constructor
	 */
	public function __construct() {
		// Add metaboxes to revision posts
		add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
		
		// Enqueue admin scripts
		add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
		
		// AJAX handlers
		add_action('wp_ajax_astp_compare_revision', array($this, 'ajax_compare_revision'));
		add_action('wp_ajax_astp_publish_revision', array($this, 'ajax_publish_revision'));
	}
	
	/**
	 * Enqueue scripts
	 */
	public function enqueue_scripts($hook) {
		$screen = get_current_screen();
		
		// Only load on test_method_revision edit screen
		if ($screen && 'test_method_revision' === $screen->post_type && 'post.php' === $hook) {
			wp_enqueue_style(
				'astp-revision-publishing',
				ASTP_VERSIONING_URL . 'assets/css/revision-publishing.css',
				array(),
				ASTP_VERSIONING_VERSION
			);
			
			wp_enqueue_script(
				'astp-revision-publishing',
				ASTP_VERSIONING_URL . 'assets/js/revision-publishing.js',
				array('jquery'),
				ASTP_VERSIONING_VERSION,
				true
			);
			
			wp_localize_script('astp-revision-publishing', 'astpPublishing', array(
				'ajaxUrl' => admin_url('admin-ajax.php'),
				'nonce' => wp_create_nonce('astp_publishing_nonce'),
				'i18n' => array(
					'compare_changes' => __('Compare Changes', 'astp-versioning'),
					'publish_revision' => __('Publish Revision', 'astp-versioning'),
					'confirm_publish' => __('Are you sure you want to publish this revision? This will update the parent test method.', 'astp-versioning'),
					'processing' => __('Processing...', 'astp-versioning')
				)
			));
		}
	}
	
	/**
	 * Add meta boxes
	 */
	public function add_meta_boxes() {
		add_meta_box(
			'astp_revision_publishing',
			__('Publish Revision', 'astp-versioning'),
			array($this, 'render_publishing_meta_box'),
			'test_method_revision',
			'side',
			'high'
		);
		
		add_meta_box(
			'astp_revision_comparison',
			__('Revision Comparison', 'astp-versioning'),
			array($this, 'render_comparison_meta_box'),
			'test_method_revision',
			'normal',
			'high'
		);
	}
	
	/**
	 * Render publishing meta box
	 */
	public function render_publishing_meta_box($post) {
		// Get revision info
		$parent_id = get_post_meta($post->ID, '_parent_test_method', true);
		$revision_type = get_post_meta($post->ID, '_revision_type', true);
		
		// Check if user has permission to publish
		$can_publish = current_user_can('publish_posts');
		
		?>
		<div class="astp-revision-publishing">
			<?php if ($parent_id && $revision_type): ?>
				<p>
					<?php _e('Status:', 'astp-versioning'); ?> 
					<strong><?php echo ucfirst($post->post_status); ?></strong>
				</p>
				
				<?php if ('publish' !== $post->post_status && $can_publish): ?>
				<p>
					<button type="button" class="button button-primary" id="compare-revision-button">
						<?php _e('Compare Changes', 'astp-versioning'); ?>
					</button>
				</p>
				
				<p>
					<button type="button" class="button button-primary" id="publish-revision-button" disabled>
						<?php _e('Publish Revision', 'astp-versioning'); ?>
					</button>
				</p>
				
				<div id="publishing-status" style="margin-top: 15px; display: none;">
					<div class="spinner is-active" style="float: none; margin: 0 10px 0 0;"></div>
					<span id="publishing-status-message"><?php _e('Processing...', 'astp-versioning'); ?></span>
				</div>
				<?php endif; ?>
				
			<?php else: ?>
				<p>
					<?php _e('Please select a parent test method and revision type before publishing.', 'astp-versioning'); ?>
				</p>
			<?php endif; ?>
		</div>
		<?php
	}
	
	/**
	 * Render comparison meta box
	 */
	public function render_comparison_meta_box($post) {
		?>
		<div class="astp-revision-comparison">
			<div id="comparison-results">
				<p><?php _e('Click "Compare Changes" to see what has been modified in this revision.', 'astp-versioning'); ?></p>
			</div>
		</div>
		<?php
	}
	
	/**
	 * AJAX handler for comparing revision
	 */
	public function ajax_compare_revision() {
		check_ajax_referer('astp_publishing_nonce', 'nonce');
		
		if (!current_user_can('edit_posts')) {
			wp_send_json_error(array('message' => __('Permission denied.', 'astp-versioning')));
		}
		
		$revision_id = isset($_POST['revision_id']) ? intval($_POST['revision_id']) : 0;
		
		if (!$revision_id) {
			wp_send_json_error(array('message' => __('Invalid revision ID.', 'astp-versioning')));
		}
		
		// Get revision info
		$revision = get_post($revision_id);
		$parent_id = get_post_meta($revision_id, '_parent_test_method', true);
		$revision_type = get_post_meta($revision_id, '_revision_type', true);
		
		if (!$revision || !$parent_id || !$revision_type) {
			wp_send_json_error(array('message' => __('Invalid revision data.', 'astp-versioning')));
		}
		
		// Get parent post
		$parent = get_post($parent_id);
		
		if (!$parent) {
			wp_send_json_error(array('message' => __('Parent test method not found.', 'astp-versioning')));
		}
		
		// Compare content based on revision type
		$comparison = $this->compare_content($parent, $revision, $revision_type);
		
		wp_send_json_success(array(
			'comparison' => $comparison,
			'revision_type' => $revision_type
		));
	}
	
	/**
	 * AJAX handler for publishing revision
	 */
	public function ajax_publish_revision() {
		check_ajax_referer('astp_publishing_nonce', 'nonce');
		
		if (!current_user_can('publish_posts')) {
			wp_send_json_error(array('message' => __('Permission denied.', 'astp-versioning')));
		}
		
		$revision_id = isset($_POST['revision_id']) ? intval($_POST['revision_id']) : 0;
		
		if (!$revision_id) {
			wp_send_json_error(array('message' => __('Invalid revision ID.', 'astp-versioning')));
		}
		
		// Get revision info
		$revision = get_post($revision_id);
		$parent_id = get_post_meta($revision_id, '_parent_test_method', true);
		$revision_type = get_post_meta($revision_id, '_revision_type', true);
		
		if (!$revision || !$parent_id || !$revision_type) {
			wp_send_json_error(array('message' => __('Invalid revision data.', 'astp-versioning')));
		}
		
		// Get parent post
		$parent = get_post($parent_id);
		
		if (!$parent) {
			wp_send_json_error(array('message' => __('Parent test method not found.', 'astp-versioning')));
		}
		
		// Publish the revision
		$result = $this->publish_revision($parent, $revision, $revision_type);
		
		if (is_wp_error($result)) {
			wp_send_json_error(array('message' => $result->get_error_message()));
		}
		
		wp_send_json_success(array(
			'message' => __('Revision published successfully.', 'astp-versioning'),
			'parent_url' => get_edit_post_link($parent_id, 'url')
		));
	}
	
	/**
	 * Compare content between parent and revision
	 */
	private function compare_content($parent, $revision, $revision_type) {
		// Parse blocks
		$parent_blocks = parse_blocks($parent->post_content);
		$revision_blocks = parse_blocks($revision->post_content);
		
		// Identify CCG and TP sections
		$parent_ccg_blocks = array();
		$parent_tp_blocks = array();
		$revision_ccg_blocks = array();
		$revision_tp_blocks = array();
		
		// Separate parent blocks
		foreach ($parent_blocks as $block) {
			if ($this->is_ccg_block($block)) {
				$parent_ccg_blocks[] = $block;
			} elseif ($this->is_tp_block($block)) {
				$parent_tp_blocks[] = $block;
			}
		}
		
		// Separate revision blocks
		foreach ($revision_blocks as $block) {
			if ($this->is_ccg_block($block)) {
				$revision_ccg_blocks[] = $block;
			} elseif ($this->is_tp_block($block)) {
				$revision_tp_blocks[] = $block;
			}
		}
		
		// Compare based on revision type
		$changes = array();
		
		if ('ccg' === $revision_type || 'both' === $revision_type) {
			$ccg_changes = $this->compare_blocks($parent_ccg_blocks, $revision_ccg_blocks);
			$changes['ccg'] = $ccg_changes;
		}
		
		if ('tp' === $revision_type || 'both' === $revision_type) {
			$tp_changes = $this->compare_blocks($parent_tp_blocks, $revision_tp_blocks);
			$changes['tp'] = $tp_changes;
		}
		
		return $changes;
	}
	
	/**
	 * Compare blocks and identify changes
	 */
	private function compare_blocks($old_blocks, $new_blocks) {
		$changes = array(
			'added' => array(),
			'removed' => array(),
			'modified' => array()
		);
		
		// Convert blocks to a comparable format
		$old_blocks_indexed = $this->index_blocks($old_blocks);
		$new_blocks_indexed = $this->index_blocks($new_blocks);
		
		// Find removed blocks
		foreach ($old_blocks_indexed as $id => $block) {
			if (!isset($new_blocks_indexed[$id])) {
				$changes['removed'][] = array(
					'id' => $id,
					'block' => $block
				);
			}
		}
		
		// Find added and modified blocks
		foreach ($new_blocks_indexed as $id => $block) {
			if (!isset($old_blocks_indexed[$id])) {
				$changes['added'][] = array(
					'id' => $id,
					'block' => $block
				);
			} else {
				// Compare block content
				$old_block = $old_blocks_indexed[$id];
				
				if ($this->is_block_modified($old_block, $block)) {
					$changes['modified'][] = array(
						'id' => $id,
						'old' => $old_block,
						'new' => $block
					);
				}
			}
		}
		
		return $changes;
	}
	
	/**
	 * Index blocks by a unique identifier
	 */
	private function index_blocks($blocks) {
		$indexed = array();
		
		foreach ($blocks as $i => $block) {
			// Use block attributes or content as identifier
			$id = isset($block['attrs']['id']) ? $block['attrs']['id'] : md5(serialize($block));
			
			$indexed[$id] = $block;
		}
		
		return $indexed;
	}
	
	/**
	 * Check if a block is modified
	 */
	private function is_block_modified($old_block, $new_block) {
		// Compare relevant parts of blocks
		return md5(serialize($old_block['innerHTML'])) !== md5(serialize($new_block['innerHTML'])) ||
			   md5(serialize($old_block['attrs'])) !== md5(serialize($new_block['attrs']));
	}
	
	/**
	 * Check if block is part of CCG section
	 */
	private function is_ccg_block($block) {
		// Check for CCG section blocks - update with your actual logic
		$ccg_block_types = array('astp/ccg-section', 'astp/ccg-content');
		return in_array($block['blockName'], $ccg_block_types);
	}
	
	/**
	 * Check if block is part of TP section
	 */
	private function is_tp_block($block) {
		// Check for TP section blocks - update with your actual logic
		$tp_block_types = array('astp/tp-section', 'astp/tp-content');
		return in_array($block['blockName'], $tp_block_types);
	}
	
	/**
	 * Publish revision
	 */
	private function publish_revision($parent, $revision, $revision_type) {
		// Get version types
		$ccg_version_type = get_post_meta($revision->ID, '_ccg_version_type', true) ?: 'minor';
		$tp_version_type = get_post_meta($revision->ID, '_tp_version_type', true) ?: 'minor';
		
		// Get current versions
		$ccg_version = get_post_meta($parent->ID, '_ccg_version', true) ?: '0.1';
		$tp_version = get_post_meta($parent->ID, '_tp_version', true) ?: '0.1';
		
		// Parse blocks
		$parent_blocks = parse_blocks($parent->post_content);
		$revision_blocks = parse_blocks($revision->post_content);
		
		// Separate blocks by section
		$parent_ccg_blocks = array();
		$parent_tp_blocks = array();
		$parent_other_blocks = array();
		
		foreach ($parent_blocks as $block) {
			if ($this->is_ccg_block($block)) {
				$parent_ccg_blocks[] = $block;
			} elseif ($this->is_tp_block($block)) {
				$parent_tp_blocks[] = $block;
			} else {
				$parent_other_blocks[] = $block;
			}
		}
		
		$revision_ccg_blocks = array();
		$revision_tp_blocks = array();
		
		foreach ($revision_blocks as $block) {
			if ($this->is_ccg_block($block)) {
				$revision_ccg_blocks[] = $block;
			} elseif ($this->is_tp_block($block)) {
				$revision_tp_blocks[] = $block;
			}
		}
		
		// Update sections based on revision type
		$updated_blocks = $parent_other_blocks;
		
		if ('ccg' === $revision_type || 'both' === $revision_type) {
			$updated_blocks = array_merge($updated_blocks, $revision_ccg_blocks);
			
			// Increment CCG version
			$new_ccg_version = $this->increment_version($ccg_version, $ccg_version_type);
			update_post_meta($parent->ID, '_ccg_version', $new_ccg_version);
			
			// Add to changelog
			$ccg_changes = $this->compare_blocks($parent_ccg_blocks, $revision_ccg_blocks);
			$this->add_to_changelog($parent->ID, 'ccg', $new_ccg_version, $ccg_changes);
		} else {
			$updated_blocks = array_merge($updated_blocks, $parent_ccg_blocks);
		}
		
		if ('tp' === $revision_type || 'both' === $revision_type) {
			$updated_blocks = array_merge($updated_blocks, $revision_tp_blocks);
			
			// Increment TP version
			$new_tp_version = $this->increment_version($tp_version, $tp_version_type);
			update_post_meta($parent->ID, '_tp_version', $new_tp_version);
			
			// Add to changelog
			$tp_changes = $this->compare_blocks($parent_tp_blocks, $revision_tp_blocks);
			$this->add_to_changelog($parent->ID, 'tp', $new_tp_version, $tp_changes);
		} else {
			$updated_blocks = array_merge($updated_blocks, $parent_tp_blocks);
		}
		
		// Update parent post content
		wp_update_post(array(
			'ID' => $parent->ID,
			'post_content' => serialize_blocks($updated_blocks)
		));
		
		// Lock the content
		update_post_meta($parent->ID, '_content_locked', 'yes');
		
		// Set revision as published
		wp_update_post(array(
			'ID' => $revision->ID,
			'post_status' => 'publish'
		));
		
		// Generate documents
		$this->generate_documents($parent->ID);
		
		return true;
	}
	
	/**
	 * Increment version based on change type
	 */
	private function increment_version($version, $change_type = 'minor') {
		$parts = explode('.', $version);
		$major = isset($parts[0]) ? intval($parts[0]) : 0;
		$minor = isset($parts[1]) ? intval($parts[1]) : 0;
		
		switch ($change_type) {
			case 'major':
				$major++;
				$minor = 0;
				break;
			case 'minor':
				$minor++;
				break;
			case 'hotfix':
				// For hotfixes, you might want to add a third number
				if (count($parts) > 2) {
					$hotfix = intval($parts[2]) + 1;
					return "{$major}.{$minor}.{$hotfix}";
				}
				$minor++;
				break;
		}
		
		return "{$major}.{$minor}";
	}
	
	/**
	 * Add changes to changelog
	 */
	private function add_to_changelog($post_id, $section, $version, $changes) {
		// Get existing changelog
		$changelog = get_post_meta($post_id, "_{$section}_changelog", true) ?: array();
		
		// Add new changes
		$changelog[$version] = array(
			'date' => current_time('mysql'),
			'changes' => $changes
		);
		
		// Save changelog
		update_post_meta($post_id, "_{$section}_changelog", $changelog);
	}
	
	/**
	 * Generate documents for the updated test method
	 */
	private function generate_documents($post_id) {
		// Call document generation functions
		if (function_exists('astp_generate_test_method_documents')) {
			astp_generate_test_method_documents($post_id);
		}
	}
}

// Initialize the class
new ASTP_Revision_Publishing();
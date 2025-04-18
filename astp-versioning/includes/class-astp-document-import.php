<?php
/**
 * Document Import
 *
 * @package ASTP_Versioning
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ASTP_Document_Import Class
 */
class ASTP_Document_Import {

	/**
	 * Constructor
	 */
	public function __construct() {
		// Enqueue scripts and styles
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		
		// Add meta box for document import
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		
		// AJAX handlers
		add_action( 'wp_ajax_astp_import_document', array( $this, 'ajax_import_document' ) );
		add_action( 'wp_ajax_astp_get_test_method_versions', array( $this, 'ajax_get_test_method_versions' ) );
		add_action( 'wp_ajax_astp_get_test_method_content', array( $this, 'ajax_get_test_method_content' ) );
		
		// Add Create Revision button to test method
		add_action( 'post_submitbox_misc_actions', array( $this, 'add_create_revision_button' ) );
	}

	/**
	 * Enqueue scripts and styles
	 */
	public function enqueue_scripts( $hook ) {
		$screen = get_current_screen();
		
		// Only enqueue on test_method_revision post type or when editing a test_method
		if ( ( 'test_method_revision' === $screen->post_type && in_array( $hook, array( 'post.php', 'post-new.php' ) ) ) || 
			 ( 'test_method' === $screen->post_type && 'post.php' === $hook ) ) {
			
			wp_enqueue_script(
				'astp-document-import',
				plugin_dir_url( dirname( __FILE__ ) ) . 'js/document-import.js',
				array( 'jquery' ),
				filemtime( plugin_dir_path( dirname( __FILE__ ) ) . 'js/document-import.js' ),
				true
			);
			
			wp_localize_script( 'astp-document-import', 'astpImport', array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce' => wp_create_nonce( 'astp_import_nonce' ),
				'createRevisionNonce' => wp_create_nonce( 'astp_create_revision_nonce' ),
			) );
			
			wp_enqueue_style(
				'astp-document-import',
				plugin_dir_url( dirname( __FILE__ ) ) . 'css/document-import.css',
				array(),
				filemtime( plugin_dir_path( dirname( __FILE__ ) ) . 'css/document-import.css' )
			);
		}
	}

	/**
	 * Add meta boxes
	 */
	public function add_meta_boxes() {
		add_meta_box(
			'astp_document_import',
			__( 'Import Document', 'astp-versioning' ),
			array( $this, 'render_document_import_meta_box' ),
			'test_method_revision',
			'normal',
			'high'
		);
	}

	/**
	 * Render document import meta box
	 */
	public function render_document_import_meta_box( $post ) {
		$parent_id = get_post_meta( $post->ID, '_parent_test_method', true );
		?>
		<div class="astp-document-import-container">
			<p><?php _e( 'Choose a source for your revision content:', 'astp-versioning' ); ?></p>
			
			<div class="astp-import-options">
				<div class="astp-import-option">
					<label>
						<input type="radio" name="import_source" value="upload" checked>
						<?php _e( 'Upload Word Document', 'astp-versioning' ); ?>
					</label>
					
					<div class="astp-import-option-content" id="upload-option-content">
						<input type="file" id="document-file" accept=".docx,.doc">
						<button type="button" class="button" id="import-document-button">
							<?php _e( 'Import Document', 'astp-versioning' ); ?>
						</button>
					</div>
				</div>
				
				<?php if ( $parent_id ) : ?>
				<div class="astp-import-option">
					<label>
						<input type="radio" name="import_source" value="current">
						<?php _e( 'Use Current Document', 'astp-versioning' ); ?>
					</label>
					
					<div class="astp-import-option-content" id="current-option-content" style="display: none;">
						<?php 
						$word_url = get_post_meta( $parent_id, '_word_document_url', true );
						if ( $word_url ) :
						?>
							<p>
								<?php 
								printf(
									__( 'Current document: <a href="%s" target="_blank">%s</a>', 'astp-versioning' ),
									esc_url( $word_url ),
									esc_html( get_the_title( $parent_id ) . ' Word Document' )
								);
								?>
							</p>
							<button type="button" class="button" id="use-current-document-button">
								<?php _e( 'Use Current Document', 'astp-versioning' ); ?>
							</button>
						<?php else : ?>
							<p><?php _e( 'No Word document found for the parent test method.', 'astp-versioning' ); ?></p>
						<?php endif; ?>
					</div>
				</div>
				
				<div class="astp-import-option">
					<label>
						<input type="radio" name="import_source" value="content">
						<?php _e( 'Use Current Content', 'astp-versioning' ); ?>
					</label>
					
					<div class="astp-import-option-content" id="content-option-content" style="display: none;">
						<p><?php _e( 'Copy the content from the current test method.', 'astp-versioning' ); ?></p>
						<button type="button" class="button" id="use-current-content-button">
							<?php _e( 'Use Current Content', 'astp-versioning' ); ?>
						</button>
					</div>
				</div>
				<?php endif; ?>
			</div>
			
			<div id="import-status" style="margin-top: 15px; display: none;">
				<div class="spinner is-active" style="float: none; margin: 0 10px 0 0;"></div>
				<span id="import-status-message"><?php _e( 'Importing document...', 'astp-versioning' ); ?></span>
			</div>
			
			<div id="import-result" style="margin-top: 15px; display: none;"></div>
		</div>
		<?php
	}

	/**
	 * Add Create Revision button to test method
	 */
	public function add_create_revision_button( $post ) {
		if ( 'test_method' !== $post->post_type || 'publish' !== $post->post_status ) {
			return;
		}
		
		// Only show for users who can edit posts
		if ( ! current_user_can( 'edit_posts' ) ) {
			return;
		}
		
		// Check if content is locked - only TP Admins and WP Admins can create revisions for locked content
		$is_locked = get_post_meta( $post->ID, '_content_locked', true ) === 'yes';
		
		if ( $is_locked && ! ( current_user_can( 'publish_posts' ) || current_user_can( 'manage_options' ) ) ) {
			return;
		}
		
		?>
		<div class="misc-pub-section">
			<button type="button" id="create-revision-button" class="button button-primary" data-post-id="<?php echo esc_attr( $post->ID ); ?>">
				<?php _e( 'Create New Revision', 'astp-versioning' ); ?>
			</button>
			<div id="create-revision-dialog" style="display: none; margin-top: 10px;">
				<p><?php _e( 'Select revision type:', 'astp-versioning' ); ?></p>
				<p>
					<label>
						<input type="radio" name="quick_revision_type" value="ccg" checked> 
						<?php _e( 'CCG Update', 'astp-versioning' ); ?>
					</label>
				</p>
				<p>
					<label>
						<input type="radio" name="quick_revision_type" value="tp"> 
						<?php _e( 'TP Update', 'astp-versioning' ); ?>
					</label>
				</p>
				<p>
					<label>
						<input type="radio" name="quick_revision_type" value="both"> 
						<?php _e( 'Both CCG and TP Update', 'astp-versioning' ); ?>
					</label>
				</p>
				<div class="revision-buttons">
					<button type="button" id="confirm-create-revision" class="button button-primary">
						<?php _e( 'Create', 'astp-versioning' ); ?>
					</button>
					<button type="button" id="cancel-create-revision" class="button">
						<?php _e( 'Cancel', 'astp-versioning' ); ?>
					</button>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * AJAX handler for getting test method versions
	 */
	public function ajax_get_test_method_versions() {
		// Check nonce
		check_ajax_referer( 'astp_get_versions_nonce', 'nonce' );
		
		// Check permissions
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'astp-versioning' ) ) );
		}
		
		$parent_id = isset( $_POST['parent_id'] ) ? intval( $_POST['parent_id'] ) : 0;
		
		if ( ! $parent_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid parent ID.', 'astp-versioning' ) ) );
		}
		
		$ccg_version = get_post_meta( $parent_id, '_ccg_version', true ) ?: '0.1';
		$tp_version = get_post_meta( $parent_id, '_tp_version', true ) ?: '0.1';
		
		wp_send_json_success( array(
			'ccg_version' => $ccg_version,
			'tp_version' => $tp_version
		) );
	}

	/**
	 * AJAX handler for document import
	 */
	public function ajax_import_document() {
		// Check nonce
		check_ajax_referer( 'astp_import_nonce', 'nonce' );
		
		// Check permissions
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'astp-versioning' ) ) );
		}
		
		$source = isset( $_POST['source'] ) ? sanitize_text_field( $_POST['source'] ) : '';
		$post_id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;
		$parent_id = isset( $_POST['parent_id'] ) ? intval( $_POST['parent_id'] ) : 0;
		
		if ( ! $post_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid post ID.', 'astp-versioning' ) ) );
		}
		
		$result = '';
		
		switch ( $source ) {
			case 'upload':
				if ( ! isset( $_FILES['document'] ) || $_FILES['document']['error'] > 0 ) {
					wp_send_json_error( array( 'message' => __( 'Error uploading file.', 'astp-versioning' ) ) );
				}
				
				$result = $this->process_document_upload( $_FILES['document'], $post_id );
				break;
				
			case 'current':
				if ( ! $parent_id ) {
					wp_send_json_error( array( 'message' => __( 'Parent test method not specified.', 'astp-versioning' ) ) );
				}
				
				$result = $this->process_current_document( $parent_id, $post_id );
				break;
				
			case 'content':
				if ( ! $parent_id ) {
					wp_send_json_error( array( 'message' => __( 'Parent test method not specified.', 'astp-versioning' ) ) );
				}
				
				$result = $this->process_current_content( $parent_id, $post_id );
				break;
				
			default:
				wp_send_json_error( array( 'message' => __( 'Invalid source.', 'astp-versioning' ) ) );
		}
		
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}
		
		wp_send_json_success( array(
			'message' => __( 'Content imported successfully.', 'astp-versioning' ),
			'content' => $result
		) );
	}

	/**
	 * Process document upload
	 */
	private function process_document_upload( $file, $post_id ) {
		// Check file type
		$file_type = wp_check_filetype( basename( $file['name'] ), array(
			'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
			'doc' => 'application/msword'
		) );
		
		if ( ! $file_type['type'] ) {
			return new WP_Error( 'invalid_file_type', __( 'Invalid file type. Only DOCX and DOC files are supported.', 'astp-versioning' ) );
		}
		
		// Upload file to WordPress
		$upload = wp_upload_bits( $file['name'], null, file_get_contents( $file['tmp_name'] ) );
		
		if ( $upload['error'] ) {
			return new WP_Error( 'upload_error', $upload['error'] );
		}
		
		// Convert document to HTML
		$html_content = $this->convert_doc_to_html( $upload['file'] );
		
		if ( is_wp_error( $html_content ) ) {
			return $html_content;
		}
		
		// Convert HTML to blocks
		$blocks = $this->convert_html_to_blocks( $html_content );
		
		// Update post content
		$update_result = wp_update_post( array(
			'ID' => $post_id,
			'post_content' => $blocks
		), true );
		
		if ( is_wp_error( $update_result ) ) {
			return $update_result;
		}
		
		return $blocks;
	}

	/**
	 * Process current document
	 */
	private function process_current_document( $parent_id, $post_id ) {
		// Get document path
		$word_path = get_post_meta( $parent_id, '_word_document_path', true );
		
		if ( ! $word_path || ! file_exists( $word_path ) ) {
			return new WP_Error( 'document_not_found', __( 'Word document not found for the parent test method.', 'astp-versioning' ) );
		}
		
		// Convert document to HTML
		$html_content = $this->convert_doc_to_html( $word_path );
		
		if ( is_wp_error( $html_content ) ) {
			return $html_content;
		}
		
		// Convert HTML to blocks
		$blocks = $this->convert_html_to_blocks( $html_content );
		
		// Update post content
		$update_result = wp_update_post( array(
			'ID' => $post_id,
			'post_content' => $blocks
		), true );
		
		if ( is_wp_error( $update_result ) ) {
			return $update_result;
		}
		
		return $blocks;
	}

	/**
	 * Process current content
	 */
	private function process_current_content( $parent_id, $post_id ) {
		// Get parent post
		$parent = get_post( $parent_id );
		
		if ( ! $parent ) {
			return new WP_Error( 'parent_not_found', __( 'Parent test method not found.', 'astp-versioning' ) );
		}
		
		// Update post content
		$update_result = wp_update_post( array(
			'ID' => $post_id,
			'post_content' => $parent->post_content
		), true );
		
		if ( is_wp_error( $update_result ) ) {
			return $update_result;
		}
		
		return $parent->post_content;
	}

	/**
	 * Convert DOC/DOCX to HTML
	 */
	private function convert_doc_to_html( $file_path ) {
		// Check if we can use external conversion service
		// For testing purposes, we'll return a basic HTML structure
		
		$html = '<h1>Test Method Title</h1>';
		$html .= '<p>This is a placeholder for the imported document content.</p>';
		$html .= '<h2>CCG Section</h2>';
		$html .= '<p>This is the CCG section content.</p>';
		$html .= '<h2>TP Section</h2>';
		$html .= '<p>This is the TP section content.</p>';
		
		return $html;
	}

	/**
	 * Convert HTML to Gutenberg blocks
	 */
	private function convert_html_to_blocks( $html ) {
		// Use Gutenberg's HTML to blocks converter
		if ( function_exists( 'parse_blocks' ) && function_exists( 'gutenberg_parse_blocks' ) ) {
			return serialize_blocks( parse_blocks( gutenberg_parse_blocks( $html ) ) );
		}
		
		// Fallback: create a simple paragraph block
		return '<!-- wp:paragraph --><p>' . esc_html( $html ) . '</p><!-- /wp:paragraph -->';
	}

	/**
	 * AJAX handler for getting test method content
	 */
	public function ajax_get_test_method_content() {
		// Check nonce
		check_ajax_referer( 'astp_import_nonce', 'nonce' );
		
		// Check permissions
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'astp-versioning' ) ) );
		}
		
		$parent_id = isset( $_POST['parent_id'] ) ? intval( $_POST['parent_id'] ) : 0;
		
		if ( ! $parent_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid parent ID.', 'astp-versioning' ) ) );
		}
		
		$parent = get_post( $parent_id );
		
		if ( ! $parent ) {
			wp_send_json_error( array( 'message' => __( 'Parent test method not found.', 'astp-versioning' ) ) );
		}
		
		wp_send_json_success( array(
			'content' => $parent->post_content
		) );
	}
}

// Initialize the class
new ASTP_Document_Import();
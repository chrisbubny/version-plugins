<?php
/**
 * Revision Post Type
 *
 * @package ASTP_Versioning
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ASTP_Revision_Post_Type Class
 */
class ASTP_Revision_Post_Type {

	/**
	 * Constructor
	 */
	public function __construct() {
		// Register the revision post type
		add_action( 'init', array( $this, 'register_post_type' ) );
		
		// Add meta boxes
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		
		// Save post meta
		add_action( 'save_post_test_method_revision', array( $this, 'save_revision_meta' ) );
		
		// Add admin columns
		add_filter( 'manage_test_method_revision_posts_columns', array( $this, 'add_admin_columns' ) );
		add_action( 'manage_test_method_revision_posts_custom_column', array( $this, 'render_admin_columns' ), 10, 2 );
		
		// Add parent filter dropdown
		add_action( 'restrict_manage_posts', array( $this, 'add_parent_filter' ) );
		add_filter( 'parse_query', array( $this, 'filter_by_parent' ) );
	}

	/**
	 * Register post type
	 */
	public function register_post_type() {
		$labels = array(
			'name'               => _x( 'Test Method Revisions', 'post type general name', 'astp-versioning' ),
			'singular_name'      => _x( 'Test Method Revision', 'post type singular name', 'astp-versioning' ),
			'menu_name'          => _x( 'TM Revisions', 'admin menu', 'astp-versioning' ),
			'name_admin_bar'     => _x( 'Revision', 'add new on admin bar', 'astp-versioning' ),
			'add_new'            => _x( 'Add New', 'revision', 'astp-versioning' ),
			'add_new_item'       => __( 'Add New Revision', 'astp-versioning' ),
			'new_item'           => __( 'New Revision', 'astp-versioning' ),
			'edit_item'          => __( 'Edit Revision', 'astp-versioning' ),
			'view_item'          => __( 'View Revision', 'astp-versioning' ),
			'all_items'          => __( 'All Revisions', 'astp-versioning' ),
			'search_items'       => __( 'Search Revisions', 'astp-versioning' ),
			'parent_item_colon'  => __( 'Parent Revisions:', 'astp-versioning' ),
			'not_found'          => __( 'No revisions found.', 'astp-versioning' ),
			'not_found_in_trash' => __( 'No revisions found in Trash.', 'astp-versioning' ),
		);

		$args = array(
			'labels'             => $labels,
			'description'        => __( 'Test Method Revisions', 'astp-versioning' ),
			'public'             => false,
			'publicly_queryable' => false,
			'show_ui'            => true,
			'show_in_menu'       => 'edit.php?post_type=test_method',
			'query_var'          => true,
			'rewrite'            => array( 'slug' => 'test-method-revision' ),
			'capability_type'    => 'post',
			'has_archive'        => false,
			'hierarchical'       => false,
			'menu_position'      => null,
			'supports'           => array( 'title', 'editor', 'author', 'revisions' ),
		);

		register_post_type( 'test_method_revision', $args );
	}

	/**
	 * Add meta boxes
	 */
	public function add_meta_boxes() {
		add_meta_box(
			'astp_revision_info',
			__( 'Revision Information', 'astp-versioning' ),
			array( $this, 'render_revision_info_meta_box' ),
			'test_method_revision',
			'side',
			'high'
		);

		add_meta_box(
			'astp_parent_test_method',
			__( 'Parent Test Method', 'astp-versioning' ),
			array( $this, 'render_parent_test_method_meta_box' ),
			'test_method_revision',
			'side',
			'high'
		);
	}

	/**
	 * Render revision info meta box
	 */
	public function render_revision_info_meta_box( $post ) {
		// Retrieve current values
		$revision_type = get_post_meta( $post->ID, '_revision_type', true );
		$parent_id = get_post_meta( $post->ID, '_parent_test_method', true );
		
		// Get current versions from parent
		$ccg_version = '';
		$tp_version = '';
		
		if ( $parent_id ) {
			$ccg_version = get_post_meta( $parent_id, '_ccg_version', true ) ?: '0.1';
			$tp_version = get_post_meta( $parent_id, '_tp_version', true ) ?: '0.1';
		}
		
		// Nonce for security
		wp_nonce_field( 'astp_revision_meta_nonce', 'astp_revision_meta_nonce' );
		
		?>
		<p>
			<label for="revision_type"><strong><?php _e( 'Revision Type:', 'astp-versioning' ); ?></strong></label><br>
			<select id="revision_type" name="revision_type" style="width: 100%;">
				<option value="" <?php selected( '', $revision_type ); ?>><?php _e( 'Select Type', 'astp-versioning' ); ?></option>
				<option value="ccg" <?php selected( 'ccg', $revision_type ); ?>><?php _e( 'CCG Update', 'astp-versioning' ); ?></option>
				<option value="tp" <?php selected( 'tp', $revision_type ); ?>><?php _e( 'TP Update', 'astp-versioning' ); ?></option>
				<option value="both" <?php selected( 'both', $revision_type ); ?>><?php _e( 'Both CCG and TP Update', 'astp-versioning' ); ?></option>
			</select>
		</p>
		
		<p>
			<strong><?php _e( 'Current Versions:', 'astp-versioning' ); ?></strong><br>
			<?php if ( $parent_id ) : ?>
				<?php _e( 'CCG:', 'astp-versioning' ); ?> <span id="current_ccg_version"><?php echo esc_html( $ccg_version ); ?></span><br>
				<?php _e( 'TP:', 'astp-versioning' ); ?> <span id="current_tp_version"><?php echo esc_html( $tp_version ); ?></span>
			<?php else : ?>
				<em><?php _e( 'Please select a parent test method.', 'astp-versioning' ); ?></em>
			<?php endif; ?>
		</p>
		
		<div id="version_type_options" style="display: none;">
			<p><strong><?php _e( 'Version Update Type:', 'astp-versioning' ); ?></strong></p>
			
			<div id="ccg_version_options" style="display: none;">
				<p>
					<label><strong><?php _e( 'CCG Update Type:', 'astp-versioning' ); ?></strong></label><br>
					<label>
						<input type="radio" name="ccg_version_type" value="minor" checked> 
						<?php _e( 'Minor Update', 'astp-versioning' ); ?>
					</label><br>
					<label>
						<input type="radio" name="ccg_version_type" value="major"> 
						<?php _e( 'Major Update', 'astp-versioning' ); ?>
					</label>
				</p>
			</div>
			
			<div id="tp_version_options" style="display: none;">
				<p>
					<label><strong><?php _e( 'TP Update Type:', 'astp-versioning' ); ?></strong></label><br>
					<label>
						<input type="radio" name="tp_version_type" value="minor" checked> 
						<?php _e( 'Minor Update', 'astp-versioning' ); ?>
					</label><br>
					<label>
						<input type="radio" name="tp_version_type" value="major"> 
						<?php _e( 'Major Update', 'astp-versioning' ); ?>
					</label>
				</p>
			</div>
		</div>
		
		<script>
			jQuery(document).ready(function($) {
				function updateVersionOptions() {
					var revisionType = $('#revision_type').val();
					
					$('#version_type_options').hide();
					$('#ccg_version_options').hide();
					$('#tp_version_options').hide();
					
					if (revisionType) {
						$('#version_type_options').show();
						
						if (revisionType === 'ccg' || revisionType === 'both') {
							$('#ccg_version_options').show();
						}
						
						if (revisionType === 'tp' || revisionType === 'both') {
							$('#tp_version_options').show();
						}
					}
				}
				
				$('#revision_type').on('change', updateVersionOptions);
				
				// Initial state
				updateVersionOptions();
			});
		</script>
		<?php
	}

	/**
	 * Render parent test method meta box
	 */
	public function render_parent_test_method_meta_box( $post ) {
		$parent_id = get_post_meta( $post->ID, '_parent_test_method', true );
		
		?>
		<p>
			<label for="parent_test_method"><strong><?php _e( 'Select Parent Test Method:', 'astp-versioning' ); ?></strong></label><br>
			<select id="parent_test_method" name="parent_test_method" style="width: 100%;">
				<option value=""><?php _e( 'Select Test Method', 'astp-versioning' ); ?></option>
				<?php
				$test_methods = get_posts( array(
					'post_type' => 'test_method',
					'post_status' => 'publish',
					'posts_per_page' => -1,
					'orderby' => 'title',
					'order' => 'ASC'
				) );
				
				foreach ( $test_methods as $test_method ) {
					printf(
						'<option value="%s" %s>%s</option>',
						esc_attr( $test_method->ID ),
						selected( $parent_id, $test_method->ID, false ),
						esc_html( $test_method->post_title )
					);
				}
				?>
			</select>
		</p>
		
		<?php if ( $parent_id ) : ?>
			<p>
				<a href="<?php echo esc_url( get_edit_post_link( $parent_id ) ); ?>" class="button" target="_blank">
					<?php _e( 'View Parent Test Method', 'astp-versioning' ); ?>
				</a>
			</p>
		<?php endif; ?>
		
		<script>
			jQuery(document).ready(function($) {
				$('#parent_test_method').on('change', function() {
					var parentId = $(this).val();
					
					if (parentId) {
						// AJAX call to get version info
						$.ajax({
							url: ajaxurl,
							type: 'POST',
							data: {
								action: 'astp_get_test_method_versions',
								parent_id: parentId,
								nonce: '<?php echo wp_create_nonce( 'astp_get_versions_nonce' ); ?>'
							},
							success: function(response) {
								if (response.success) {
									$('#current_ccg_version').text(response.data.ccg_version);
									$('#current_tp_version').text(response.data.tp_version);
								}
							}
						});
					} else {
						$('#current_ccg_version').text('');
						$('#current_tp_version').text('');
					}
				});
			});
		</script>
		<?php
	}

	/**
	 * Save revision meta
	 */
	public function save_revision_meta( $post_id ) {
		// Security checks
		if ( ! isset( $_POST['astp_revision_meta_nonce'] ) || ! wp_verify_nonce( $_POST['astp_revision_meta_nonce'], 'astp_revision_meta_nonce' ) ) {
			return;
		}
		
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		
		// Save revision type
		if ( isset( $_POST['revision_type'] ) ) {
			update_post_meta( $post_id, '_revision_type', sanitize_text_field( $_POST['revision_type'] ) );
		}
		
		// Save parent test method
		if ( isset( $_POST['parent_test_method'] ) ) {
			update_post_meta( $post_id, '_parent_test_method', intval( $_POST['parent_test_method'] ) );
		}
		
		// Save version types
		if ( isset( $_POST['ccg_version_type'] ) ) {
			update_post_meta( $post_id, '_ccg_version_type', sanitize_text_field( $_POST['ccg_version_type'] ) );
		}
		
		if ( isset( $_POST['tp_version_type'] ) ) {
			update_post_meta( $post_id, '_tp_version_type', sanitize_text_field( $_POST['tp_version_type'] ) );
		}
	}

	/**
	 * Add admin columns
	 */
	public function add_admin_columns( $columns ) {
		$new_columns = array();
		
		// Insert columns after title
		foreach ( $columns as $key => $value ) {
			$new_columns[$key] = $value;
			
			if ( 'title' === $key ) {
				$new_columns['parent_test_method'] = __( 'Parent Test Method', 'astp-versioning' );
				$new_columns['revision_type'] = __( 'Revision Type', 'astp-versioning' );
			}
		}
		
		return $new_columns;
	}

	/**
	 * Render admin columns
	 */
	public function render_admin_columns( $column, $post_id ) {
		switch ( $column ) {
			case 'parent_test_method':
				$parent_id = get_post_meta( $post_id, '_parent_test_method', true );
				
				if ( $parent_id ) {
					$parent = get_post( $parent_id );
					
					if ( $parent ) {
						printf(
							'<a href="%s">%s</a>',
							esc_url( get_edit_post_link( $parent_id ) ),
							esc_html( $parent->post_title )
						);
					} else {
						_e( 'Parent not found', 'astp-versioning' );
					}
				} else {
					_e( 'None', 'astp-versioning' );
				}
				break;
				
			case 'revision_type':
				$revision_type = get_post_meta( $post_id, '_revision_type', true );
				
				switch ( $revision_type ) {
					case 'ccg':
						_e( 'CCG Update', 'astp-versioning' );
						break;
						
					case 'tp':
						_e( 'TP Update', 'astp-versioning' );
						break;
						
					case 'both':
						_e( 'CCG & TP Update', 'astp-versioning' );
						break;
						
					default:
						_e( 'Not specified', 'astp-versioning' );
						break;
				}
				break;
		}
	}

	/**
	 * Add parent filter
	 */
	public function add_parent_filter() {
		global $typenow;
		
		if ( 'test_method_revision' !== $typenow ) {
			return;
		}
		
		$parent_id = isset( $_GET['parent_test_method'] ) ? intval( $_GET['parent_test_method'] ) : 0;
		
		$test_methods = get_posts( array(
			'post_type' => 'test_method',
			'post_status' => 'publish',
			'posts_per_page' => -1,
			'orderby' => 'title',
			'order' => 'ASC'
		) );
		
		if ( empty( $test_methods ) ) {
			return;
		}
	     }
		
			/**
			 * Filter by parent
			 */
			public function filter_by_parent( $query ) {
				global $pagenow, $typenow;
				
				if ( 'edit.php' !== $pagenow || 'test_method_revision' !== $typenow ) {
					return $query;
				}
				
				if ( ! isset( $_GET['parent_test_method'] ) || empty( $_GET['parent_test_method'] ) ) {
					return $query;
				}
				
				$parent_id = intval( $_GET['parent_test_method'] );
				
				$query->query_vars['meta_query'] = array(
					array(
						'key' => '_parent_test_method',
						'value' => $parent_id,
						'compare' => '=',
					),
				);
				
				return $query;
			}
		}
		
		// Initialize the class
		new ASTP_Revision_Post_Type();

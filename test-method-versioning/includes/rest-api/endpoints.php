<?php
/**
 * REST API Controller for Test Method Versioning
 */
class TM_REST_Controller {

	/**
	 * Register routes
	 */
	public function register_routes() {
		register_rest_route('tm-versioning/v1', '/workflow/(?P<id>\d+)', array(
			'methods' => WP_REST_Server::EDITABLE,
			'callback' => array($this, 'update_workflow_status'),
			'permission_callback' => array($this, 'check_update_permission'),
			'args' => array(
				'id' => array(
					'validate_callback' => function($param) {
						return is_numeric($param);
					}
				),
				'status' => array(
					'required' => true,
					'validate_callback' => function($param) {
						return in_array($param, array('draft', 'submitted', 'approved', 'rejected', 'published', 'revision'));
					}
				),
				'comment' => array(
					'type' => 'string'
				),
				'version' => array(
					'type' => 'string'
				)
			)
		));

		register_rest_route('tm-versioning/v1', '/version/(?P<id>\d+)', array(
			'methods' => WP_REST_Server::EDITABLE,
			'callback' => array($this, 'handle_version_action'),
			'permission_callback' => array($this, 'check_version_permission'),
			'args' => array(
				'id' => array(
					'validate_callback' => function($param) {
						return is_numeric($param);
					}
				),
				'action' => array(
					'required' => true,
					'validate_callback' => function($param) {
						return in_array($param, array('create', 'rollback', 'unlock'));
					}
				),
				'version_type' => array(
					'type' => 'string',
					'validate_callback' => function($param) {
						return in_array($param, array('basic', 'minor', 'major', 'hotfix', 'custom'));
					}
				),
				'version' => array(
					'type' => 'string'
				)
			)
		));

		register_rest_route('tm-versioning/v1', '/versions/(?P<id>\d+)', array(
			'methods' => WP_REST_Server::READABLE,
			'callback' => array($this, 'get_versions'),
			'permission_callback' => array($this, 'check_read_permission'),
			'args' => array(
				'id' => array(
					'validate_callback' => function($param) {
						return is_numeric($param);
					}
				)
			)
		));

		register_rest_route('tm-versioning/v1', '/approvals/(?P<id>\d+)', array(
			'methods' => WP_REST_Server::READABLE,
			'callback' => array($this, 'get_approvals'),
			'permission_callback' => array($this, 'check_read_permission'),
			'args' => array(
				'id' => array(
					'validate_callback' => function($param) {
						return is_numeric($param);
					}
				)
			)
		));

		register_rest_route('tm-versioning/v1', '/changelog/(?P<id>\d+)', array(
			'methods' => WP_REST_Server::READABLE,
			'callback' => array($this, 'get_changelog'),
			'permission_callback' => array($this, 'check_read_permission'),
			'args' => array(
				'id' => array(
					'validate_callback' => function($param) {
						return is_numeric($param);
					}
				),
				'version' => array(
					'type' => 'string'
				)
			)
		));

		register_rest_route('tm-versioning/v1', '/export/(?P<id>\d+)', array(
			'methods' => WP_REST_Server::EDITABLE,
			'callback' => array($this, 'generate_document'),
			'permission_callback' => array($this, 'check_export_permission'),
			'args' => array(
				'id' => array(
					'validate_callback' => function($param) {
						return is_numeric($param);
					}
				),
				'type' => array(
					'required' => true,
					'validate_callback' => function($param) {
						return in_array($param, array('word', 'pdf'));
					}
				),
				'version' => array(
					'type' => 'string'
				)
			)
		));
	}

	// Endpoint methods and permission checks would be included here
}

/**
 * Initialize REST API
 */
function tm_rest_api_init() {
	$controller = new TM_REST_Controller();
	$controller->register_routes();
}
add_action('rest_api_init', 'tm_rest_api_init');
<?php
class TemplateAPI {

	protected $api_base               = 'edupack-api/v1';
	protected $templates_endpoint     = '/templates';
	protected $all_templates_endpoint = '/templates/all';
	protected $builds_endpoint        = '/templates/builder';

	/**
	 * Constructor registers API routes
	 *
	 * @uses "rest_api_init" action
	 */
	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'add_routes' ) );
	}

	/**
	 * Does what it says on the tin
	 */
	public function add_routes() {

	// POST - Set site template status
		register_rest_route(
			$this->api_base,
			$this->templates_endpoint,
			array(
				'methods'              => 'POST',
				'callback'             => array( $this, 'set_template_status' ),
				'args'                 => array(
					'blog-id'         => array(
						'required'          => true,
						'sanitize_callback' => 'sanitize_blog_ids',
					),
					'template-status' => array(
						'required'          => true,
						'sanitize_callback' => 'sanitize_bool',
					),
				),
				'permission_callback' => array( $this, 'permissions' ),
			)
	);

	// POST - Set site discoverability
		register_rest_route(
			$this->api_base,
			$this->templates_endpoint,
			array(
				'methods'              => 'POST',
				'callback'             => array( $this, 'set_discoverability' ),
				'args'                 => array(
					'blog-id'         => array(
						'required'          => true,
						'sanitize_callback' => 'sanitize_blog_ids',
					),
					'discoverability' => array(
						'required'          => true,
						'sanitize_callback' => 'sanitize_bool',
					),
				),
				'permission_callback' => array( $this, 'permissions' ),
			)
	);

	// GET - Get templates
		register_rest_route(
			$this->api_base,
			$this->templates_endpoint,
			array(
				'methods'              => 'GET',
				'callback'             => array( $this, 'get_templates' ),
				'args'                 => array(),
				'permission_callback' => array( $this, 'permissions' ),
			)
	);

	// POST - Bulk template updates
		register_rest_route(
			$this->api_base,
			$this->all_templates_endpoint,
			array(
				'methods'              => 'POST',
				'callback'             => array( $this, 'bulk_update_templates' ),
				'args'                 => array(
					'templates' => array(
						'sanitize_callback' => 'sanitize_templates',
					),
				),
				'permission_callback' => array( $this, 'permissions' ),
			)
	);

	// POST - Build site from template and config
		register_rest_route(
			$this->api_base,
			$this->builds_endpoint,
			array(
				'methods'              => 'POST',
				'callback'             => array( $this, 'build_from_template' ),
				'args'                 => array(
					'config' => array(
						'required'          => true,
						'sanitize_callback' => 'sanitize_build_config',
					),
				),
				'permission_callback' => array( $this, 'permissions' ),
			)
		);
	}

	/**
	 * Check request permissions - users should be able to manage_options
	 *
	 * @return bool
	 */
	public function permissions() {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Validate/sanitize blog_id for template/discoverability settings
	 */
	public function sanitize_blog_ids() {
		// TODO
		return 0;
	}

	/**
	 * Validate/sanitize bool states for template/discoverability settings
	 */
	public function sanitize_bool() {
		// TODO
		return false;
	}

	/**
	 * Validate/sanitize templates array
	 */
	public function sanitize_templates( $param, $request, $key ) {
		// TODO
		return $param;
	}

	/**
	 * Validate/sanitize template build config
	 */
	public function sanitize_build_config() {
		// TODO
		return '';
	}

	/**
	 * Get all templates
	 *
	 * @param WP_REST_Request $request
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_templates( WP_REST_Request $request ) {
		return rest_ensure_response( TemplateFunctions::get_templates() );
	}

	/**
	 * Set is_template meta value for the site with the given ID
	 *
	 * @param WP_REST_Request $request
	 * @return WP_Error|WP_REST_Response
	 */
	public static function set_template_status( WP_REST_Request $request ) {
		$blog_id         = $request->get_param( 'blog-id' );
		$template_status = $request->get_param( 'template-status' );
		return rest_ensure_response( TemplateFunctions::set_template_status( $blog_id, $template_status ) );
	}

	/**
	 * Set is_discoverable meta value for the site with the given ID.
	 *
	 * @param WP_REST_Request $request
	 * @return WP_Error|WP_REST_Response
	 */
	public static function set_discoverability( WP_REST_Request $request ) {
		$blog_id        = $request->get_param( 'blog-id' );
		$dicoverability = $request->get_param( 'dicoverability' );
		return rest_ensure_response( TemplateFunctions::set_discoverability( $blog_id, $dicoverability ) );
	}

	/**
	 * Updates a whole bunch of template/discoverability statuses at once.
	 *
	 * @param WP_REST_Request $request
	 * @return WP_Error|WP_REST_Response
	 */
	public static function bulk_update_templates( WP_REST_Request $request ) {
		$templates = $request->get_params();
		return rest_ensure_response( TemplateFunctions::bulk_update_templates( $templates ) );
	}

	/**
	 * Build site from template
	 *
	 * @param WP_REST_Request $request
	 * @return WP_Error|WP_REST_Response
	 */
	public function build_from_template( WP_REST_Request $request ) {
		$config = $request->get_param( 'config' );
		return rest_ensure_response( TemplateFunctions::build_from_template( $config ) );
	}
}

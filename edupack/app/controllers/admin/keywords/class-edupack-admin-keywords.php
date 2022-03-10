<?php
/**
 * Edupack keywords
 *
 * Hanldes the keyword settings for wordpress network admin
 *
 * @package edupack
 * @version 1.0.0
 */

require_once edupack_get_class( '/controllers/admin/keywords/KeywordsAdminMenu.php' );
require_once edupack_get_class( '/controllers/admin/keywords/KeywordsSuperAdminMenu.php' );

/**
 * Edupack admin keywords
 */
class Edupack_Admin_Keywords extends Edupack {

	/**
	 * The class constructor
	 */
	public function __construct() {
		$this->add_filters();
		$this->add_actions();
		$this->add_remove_actions();
	}

	/**
	 * Add actions for class
	 */
	public function add_actions() : void {
		add_action( 'init', array( $this, 'keyword_manager_construct' ) );
		add_action( 'rest_api_init', array( $this, 'add_rest_api' ) );
	}

	/**
	 * Add filters for class
	 *
	 * @return void
	 */
	public function add_filters() :void {}

	/**
	 * Add remove actions
	 *
	 * @return void
	 */
	public function add_remove_actions() : void {}

	/**
	 * Manages rest api endpoints
	 *
	 * @return void
	 */
	public function add_rest_api() : void {
		register_rest_route(
			'edupack/v1',
			'/network-site-meta-tags',
			array(
				'methods'  => 'GET',
				'callback' => array( $this, 'edupack_get_site_meta_data' ),
				'permission_callback' => function( $request ) {
					return true;
				},
			)
		);
	}

	/**
	 * Keyword Manager Contructor
	 * 
	 * @return void
	 */
	public function keyword_manager_construct() {
		if ( is_admin() && class_exists( 'KeywordsAdminMenu' ) ) {
			new KeywordsAdminMenu( $this->get_plugin_url() );
		}
		if ( is_super_admin() && class_exists( 'KeywordsSuperAdminMenu' ) ) {
			new KeywordsSuperAdminMenu( $this->get_plugin_url() );
		}
	}

	/**
	 * Custom API endpoing to get a single site's meta keyword info
	 *
	 * @return array $response
	 */
	public function edupack_get_site_meta_data() {
		$site_keywords = get_site_meta( get_current_blog_id(), 'site-tags', true );

		if ( $site_keywords ) {
			$site_keywords = explode( ',', $site_keywords );
		} else {
			$site_keywords = array();
		}

		$network_keywords = get_network_option( null, 'site-tags', array() );
		if ( $network_keywords ) {
			$network_keywords = explode( ',', $network_keywords );
		} else {
			$network_keywords = array();
		}

		$response = array();
		foreach ( $network_keywords as $keyword ) {
			$enabled    = in_array( $keyword, $site_keywords );
			$response[] = array(
				'name'    => $keyword,
				'enabled' => $enabled,
			);
		}

		return $response;
	}
}

new Edupack_Admin_Keywords();

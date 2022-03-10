<?php
/**
 * Edupack network admin dashboard
 *
 * Hanldes the main settings dashboard for edupack network settings
 *
 * @package edupack
 * @version 1.0.0
 */

/**
 * Edupack network admin dashboard
 */
class Edupack_Network_Settings_Dashboard extends Edupack {

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

		// Network menu registers.
		add_action( 'network_admin_menu', array( $this, 'edupack_network_dashboard' ) );
		add_action( 'network_admin_menu', array( $this, 'edupack_dashboard' ) );
		add_action( 'network_admin_menu', array( $this, 'edupack_settings' ) );
		add_action( 'network_admin_menu', array( $this, 'hide_edupack_submenu' ) );
		add_action( 'after_setup_theme', array( $this, 'remove_core_updates' ) );
	}

	/**
	 * Remove core updates notifications from edupack dashboard
	 */
	public function remove_core_updates() {

		$ignore_pages = array(
			'dashboard',
		);

		$page = $_GET['page'] ?? '';

		if ( in_array( $page, $ignore_pages, true ) ) {
			add_filter( 'pre_option_update_core', '__return_null' );
			add_filter( 'pre_site_transient_update_core', '__return_null' );
		}
	}

	/**
	 * Add filters for class
	 */
	public function add_filters() : void {}

	/**
	 * Add remove actions
	 */
	public function add_remove_actions() : void {}

	/**
	 * Remove the main submenu from the sidebar.
	 */
	public function hide_edupack_submenu() : void {
		global $submenu;
		unset( $submenu['edupack'][0] );
	}

	/**
	 * Edupack network dashboard
	 */
	public function edupack_network_dashboard() : void {
		add_menu_page(
			'Edupack',
			'Edupack',
			'manage_options',
			'edupack',
			array( $this, 'network_edupack_dashboard_template_output' ),
			$this->get_icon_svg(),
			3
		);
	}

	/**
	 * Edupack network dashboard
	 */
	public function edupack_dashboard() : void {
		add_submenu_page(
			'edupack',
			'Dashboard',
			'Dashboard',
			'manage_options',
			'dashboard',
			array( $this, 'network_edupack_dashboard_template_output' )
		);
	}

	/**
	 * Edupack network dashboard
	 */
	public function edupack_settings() : void {
		add_submenu_page(
			'edupack',
			'Settings',
			'Settings',
			'manage_options',
			'settings',
			array( $this, 'network_edupack_settings_template_output' )
		);
	}

	/**
	 * Edupack template output
	 */
	public function network_edupack_dashboard_template_output() : void {
		$this->include_admin_template( 'dashboard/dashboard.php' );
	}

	/**
	 * Edupack template output
	 */
	public function network_edupack_settings_template_output() : void {
		$this->include_admin_template( 'settings/dashboard.php' );
	}
}

new Edupack_Network_Settings_Dashboard();

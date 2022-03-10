<?php
/**
 * Edupack admin dashboard
 *
 * Hanldes the main wordpress dashboard for edupack
 *
 * @package edupack
 * @version 1.0.0
 */

/**
 * Edupack admin dashboard
 */
class Edupack_Admin_Dashboard extends Edupack {

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
	public function add_actions() {
		add_action( 'admin_init', array( $this, 'edupack_admin_color_scheme' ) );
		add_action( 'user_register', array( $this, 'update_user_admin_colour' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'edupack_admin_bar_skin' ) );
		add_action( 'welcome_panel', array ( $this, 'edupack_dashboard_config_welcome_message' ) );
		add_action( 'admin_enqueue_scripts', array ( $this, 'edupack_styles' ) );
	}

	/**
	 * Add filters for class
	 */
	public function add_filters() {}

	/**
	 * Add remove actions
	 */
	public function add_remove_actions() : void {
		remove_action( 'welcome_panel', 'wp_welcome_panel' );
	}

	/**
	 * Add Plugin styles
	 *
	 * @return void
	 */
	public function edupack_styles() : void {
		wp_enqueue_style( 'edupack-admin-styles', $this->get_dist_css_url( 'style-admin.min.css' ), array(), '0.0.1' );
	}

	/**
	 * Add Adminbar Skin
	 *
	 * @return void
	 */
	public function edupack_admin_bar_skin() : void {
		$current_color = get_user_option( 'admin_color' );
		if ( 'edupack' === $current_color ) {
			wp_enqueue_style( 'edupack-admin-bar', $this->get_dist_css_url( 'admin-bar.css' ), array(), '0.0.1' );
		}
	}

	/**
	 * Add Dashboard Skin
	 *
	 * @return void
	 */
	public function edupack_admin_color_scheme() : void {
		$edupack_colors = array(
			'#0d1e4c',
			'#fbf4ec',
			'#ef4437',
			'#cb8e29',
		);

		wp_admin_css_color(
			'edupack',
			__( 'Edupack' ),
			$this->get_dist_css_url( 'admin-color-scheme.css' ),
			$edupack_colors
		);
	}

	/**
	 * Update user admin colour
	 *
	 * @param int $user_id - The user ID
	 *
	 * @return void
	 */
	public function update_user_admin_colour( int $user_id ) : void {
		$args = array(
			'ID'          => $user_id,
			'admin_color' => 'edupack',
		);

		wp_update_user( $args );
	}

	/**
	 * Show Custom Welcome Message
	 */
	public function edupack_dashboard_config_welcome_message() {
		$this->include_admin_template( 'admin-dashboard.php' );
	}
}

new Edupack_Admin_Dashboard();

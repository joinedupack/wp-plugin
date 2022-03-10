<?php
/**
 * Edupack site owners
 *
 * Hanldes the site owner for edupack
 *
 * @package edupack
 * @version 1.0.0
 */

/**
 * Edupack site owner
 */
class Edupack_Site_Visibility extends Edupack {

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
		add_action( 'admin_enqueue_scripts', array( $this, 'edupack_user_roles_hide_menus' ) );
		add_action( 'admin_menu', array( $this, 'edupack_visibility_control_admin_init' ) );
		add_action( 'init', array( $this, 'edupack_nonpublic_site_redirect' ), 100, 0 );
		add_action( 'admin_enqueue_scripts', array( $this, 'edupack_enqueue_custom_admin_script' ) );

		add_action(
			'load-toplevel_page_edupack-visibility-control',
			array(
				$this,
				'edupack_update_site_visbility_admin_redirect',
			)
		);

		add_action(
			'load-toplevel_page_edupack-visibility-control-publish',
			array(
				$this,
				'edupack_update_site_visbility_admin_redirect',
			)
		);

		add_action(
			'load-toplevel_page_edupack-visibility-control-unpublish',
			array(
				$this,
				'edupack_update_site_visbility_admin_redirect',
		 	)
		);
	}

	/**
	 * Add filters for class
	 */
	public function add_filters() {}

	/**
	 * Add removals for class
	 */
	public function add_remove_actions() {}

	/**
	 * Hide Menus for non-super admins
	 * 
	 * @package Edupack
	 */
	public function edupack_user_roles_hide_menus() {
		if ( ! is_super_admin() ) {
			wp_enqueue_style( 'hide-menu-items', $this->get_plugin_url() . 'style-user-roles/hiding.css' );
		}
	}

	/**
	 * Add 'Activate Site' menu item, if site is not active
	 */
	public function edupack_visibility_control_admin_init() {
		$site_id = get_current_blog_id();
		$site    = get_site( $site_id );

		$archiving_permissions = $this->get_setting_option( 'edupack_archival_policies', 'archiving_permissions' );
		$publishing_permissions = $this->get_setting_option( 'edupack_archival_policies', 'publishing_permissions' );

		// TODO: Clicking publish/unpublish does nothing.
		if ( ! $site->public || $site->deleted ) {

			// If we are an admin and admins are allowed, or we are super admin
			if ( ( current_user_can( 'administrator' ) && '1' === $publishing_permissions ) || is_super_admin() ) {
				add_menu_page(
					'Activate Site',
					'Publish Site',
					'delete_site',
					'edupack-visibility-control-publish',
					array( $this, 'edupack_update_site_visiblity' ),
					'dashicons-yes-alt',
					2
				);
			}
		} elseif ( $site->public ) {
	
			if ( ( current_user_can( 'administrator' ) && '1' === $archiving_permissions ) || is_super_admin() ) {
				add_menu_page(
					'Deactivate Site',
					'Archive Site',
					'delete_site',
					'edupack-visibility-control-unpublish',
					array( $this, 'edupack_update_site_visiblity' ),
					'dashicons-archive',
					2
				);
			}
		}
	}

	/**
	 * add_menu_page above didn't like not getting something as a callback, so here we are, just in case
	 */
	public function edupack_update_site_visiblity() {
		return;
	}

	/**
	 * Toggle site 'public' status
	 */
	public function edupack_update_site_visbility_admin_redirect() {
		$site_id = get_current_blog_id();
		$site    = get_site( $site_id );

		if ( $site->public ) {
			wp_update_site( $site_id, array( 'public' => 0 ) );
		} else {
			error_log( 'SCREENSHOT:Publishing site ' . $site_id );
			wp_update_site(
				$site_id,
				array(
					'public'  => 1,
					'deleted' => 0,
				)
				);

			// Generate a screenshot
			if ( function_exists( 'edupack_update_homepage_screenshot' ) ) {
				$blog_id = get_current_blog_id();
				edupack_update_homepage_screenshot( $blog_id );
			}
		}

		wp_safe_redirect( get_admin_url( $site_id ) );
		exit();
	}

	/**
	 * Prevent non-admins from accessing non-published sites.
	 */
	public function edupack_nonpublic_site_redirect() {
		$site_id       = get_current_blog_id();
		$site          = get_site( $site_id );
		$is_public     = $site->public;
		$is_login_page = in_array( $GLOBALS['pagenow'], array( 'wp-login.php', 'wp-register.php' ) );
		$user_id       = get_current_user_id();
		$is_user_admin = user_can( $user_id, 'administrator' );

		// Redirect non-admins to error page with link to login URL if site is nonpublic
		if ( ! $is_public && ! $is_user_admin && ! $is_login_page ) {

			// Do not redirect if we're looking at the index on a site preview
			if ( isset( $_GET['edupack_template_preview'] ) && $_GET['edupack_template_preview'] === 'true' && $GLOBALS['pagenow'] === 'index.php' ) {
				return;
			}

			global $wp;
			$redirect  = add_query_arg( $wp->query_vars, home_url( $wp->request ) );
			$login_url = wp_login_url( $redirect );
			$message   = "This site is unpublished. If you're a site administator, please log in <a href = \"$login_url\">here</a>.";
			wp_die( $message, 'Unauthorized', 401 );
		}
	}

	/**
	 * Edupack enqueue custom admin scripts
	 */
	public function edupack_enqueue_custom_admin_script() {
		wp_register_script( 'edupack-visibility-admin-js', $this->get_dist_js_url( 'edupack-visibility-control.js' ), array( 'jquery-core' ), false, true );
		wp_enqueue_script( 'edupack-visibility-admin-js' );
	}
}

new Edupack_Site_Visibility();


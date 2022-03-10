<?php
/**
 * Edupack onboarding
 *
 * Hanldes the site owner for edupack
 *
 * @package edupack
 * @version 1.0.0
 */

/**
 * Edupack onboarding
 */
class Edupack_Onboarding extends Edupack {

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
		add_action( 'init', array( $this, 'template_api' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'edupack_enqueue_preview_messaging' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'edupack_enqueue_onboarding' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'edupack_enqueue_signup' ) );
		add_action( 'before_signup_header', array( $this, 'edupack_redirect_signup_to_onboarding' ) );
		add_action( 'wp_initialize_site', array( $this, 'edupack_build_site_from_template' ), 9999, 2 );
	}

	/**
	 * Add filters for class
	 */
	public function add_filters() {
		add_filter( 'wp_signup_location', array( $this, 'edupack_signup_page_filter' ) );
	}

	/**
	 * Add removers for class
	 */
	public function add_remove_actions() {}

	/**
	 * Construct template manager menu and Template API objects on init
	 * API Should always be constructed.
	 *
	 * @package Edulab
	 */
	public function template_api() {
		if ( is_admin() ) {
			new TemplateAdminMenu( $this->get_plugin_url() );
		}
		new TemplateAPI();
	}

	/**
	 * Edupack sigup filter
	 *
	 * @param string $url.
	 * 
	 * @return string $url
	 */
	public function edupack_signup_page_filter( $url ) : string {

		if ( function_exists( 'get_current_screen' ) ) {
			  $screen = get_current_screen();
			  if ( $screen->id === 'my-sites' ) {
				switch_to_blog( get_main_site_id() );

				$onboarding_query = new WP_Query(
					array(
						'post_type'  => 'any',
						'meta_key'   => '_wp_page_template',
						'meta_value' => 'onboarding-page.php',
					)
				);

				if ( $onboarding_query->have_posts() ) {
					$onboarding_query->the_post();
					$url = get_the_permalink();
				}

				restore_current_blog();
			}
		}
		return $url;
	}

	/**
	 * Adds message passing support for pages loaded via the onboarding form.
	 * This allows us to reactively update values on the template with user input.
	 */
	public function edupack_enqueue_preview_messaging() {
		if ( isset( $_GET['edupack_template_preview'] ) && $_GET['edupack_template_preview'] === 'true' ) {
			wp_register_script( 'edupack-preview-messaging', $this->get_plugin_url() . 'js/preview-messaging.js', array( 'jquery' ) );
			wp_enqueue_script( 'edupack-preview-messaging' );
		}
	}

	/**
	 * Enqueue everything for the onboarding
	 */
	public function edupack_enqueue_onboarding() {
		if ( is_page_template( 'onboarding-page.php' ) ) {

			wp_register_script( 'edupack-onboarding', $this->get_dist_js_url( 'onboarding.js' ), array( 'jquery' ), $version = '0.0.1' );
			wp_enqueue_script( 'edupack-onboarding' );

			wp_register_style( 'edupack-onboarding', $this->get_dist_css_url( '_legacy/onboarding.min.css' ), $deps = '', $version = '0.0.1' );
			wp_enqueue_style( 'edupack-onboarding' );

			wp_enqueue_style( 'choices', $this->get_dist_css_url( 'choices.min.css' ) );

			wp_enqueue_script( 'choices', $this->get_dist_js_url( 'choices.min.js' ) );
			wp_enqueue_script( 'choices-setup', $this->get_dist_js_url( 'choices-setup.js' ), array( 'jquery' ) );
		}
	}

	/**
	 * Enqueue custom styles on signup page
	 */
	public function edupack_enqueue_signup() {
		if ( $GLOBALS['pagenow'] === 'wp-signup.php' ) {
			  wp_enqueue_style( 'custom-signup', $this->get_dist_css_url( 'signup.css' ) );
		}
	}

	/**
	 * Redirect logged-in users trying to visit signup page to onboarding page
	 */
	public function edupack_redirect_signup_to_onboarding() {
		if ( $GLOBALS['pagenow'] === 'wp-signup.php' && is_user_logged_in() ) {
			switch_to_blog( get_main_site_id() );
			$onboarding_query = new WP_Query(
				array(
					'post_type'  => 'any',
					'meta_key'   => '_wp_page_template',
					'meta_value' => 'onboarding-page.php',
				)
			);

			if ( $onboarding_query->have_posts() ) {
				$onboarding_query->the_post();
				$url = get_the_permalink();
			}

			restore_current_blog();
			wp_redirect( $url );
		}
	}

	/**
	 * Copy logic runs on wp_initialize_site hook.
	 * Set priority to very high so it runs after everything else.
	 * accepts 2 params so we can get to meta
	 */
	public function edupack_build_site_from_template( $new_site, $args ) {

		$blog_id = $new_site->blog_id;
		$user_id = isset( $args['user_id'] ) ? intval( $args['user_id'] ) : 0;

		// Get variables from POST request
		$template_site_id = isset( $_POST['template-select'] ) ? $_POST['template-select'] : null;
		$site_title       = isset( $_POST['blog_title'] ) ? $_POST['blog_title'] : null;
		$site_tagline     = isset( $_POST['site_tagline'] ) ? $_POST['site_tagline'] : null;
		$site_keywords    = isset( $_POST['site_keywords'] ) ? $_POST['site_keywords'] : null;

		// The super admin can create a blog without a template
		// TODO: Determine if this is behavior we want.
		if ( is_network_admin() && ! $template_site_id ) {
			return;
		}

		// Default template site to 2
		// TODO: better default template behavior, as opposed to defaulting to 2
		if ( $template_site_id === null ) {
			$template_site_id = 2;
		}

		// Pack 'em up
		$config = array(
			'new_site_id'      => $blog_id,
			'user_id'          => $user_id,
			'template_site_id' => $template_site_id,
			'site_title'       => $site_title,
			'site_tagline'     => $site_tagline,
			'site_keywords'    => $site_keywords,
		);

		// Switch to the blog that was just created
		switch_to_blog( $blog_id );

		// Build it up, build it up
		// https://www.youtube.com/watch?v=N5ixEHJvbJE
		SiteTemplater::build_from_template( $config );

		// Switch back to our current blog
		restore_current_blog();
	}
}

new Edupack_Onboarding();
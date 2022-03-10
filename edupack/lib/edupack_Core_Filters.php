<?php

class edupack_Core_Filters {

	public static function add_filters() {
		$filters = array(
			array( 'login_title', 'filter_login_title' ),
			array( 'login_redirect', 'filter_login_redirect', 999, 3 ),
			array( 'allowed_redirect_hosts', 'filter_allowed_redirect_hosts' ),
			array( 'get_blogs_of_user', 'filter_out_main_blog_on_my_sites' ),
			array( 'gettext', 'no_sites_text_filter', 20, 3 ),
		);
		foreach ( $filters as $filter ) {
			$hook      = $filter[0];
			$func      = $filter[1];
			$priority  = $filter[2] ?? 10; // default value
			$arg_count = $filter[3] ?? 1; // default value
			if ( method_exists( 'edupack_Core_Filters', "$func" ) ) {
					add_filter( $hook, "edupack_Core_Filters::$func", $priority, $arg_count );
			}
		}
	}

	/**
	 * Strips 'WordPress' and the preceding em-dash from the login title
	 */
	public static function filter_login_title( $title ) {
		if ( strpos( $title, ' &#8212; WordPress' ) ) {
			$title = str_replace( ' &#8212; WordPress', '', $title );
		}
		return $title;
	}

	/**
	 * Changes the default post-login screen to My Sites
	 */
	public static function filter_login_redirect( $url, $query, $user ) {

		// Do we have a redirect URL?
		if ( isset( $_REQUEST['redirect_to'] ) && $_REQUEST['redirect_to'] !== get_admin_url() ) {
			$redirect   = $_REQUEST['redirect_to'];
			$site_id    = get_blog_id_from_url( parse_url( $redirect, PHP_URL_HOST ) );
			$admin_page = get_admin_url( $site_id );

			// We only filter when we are redirecting to the main dashboard
			// Since this filter only runs on login, this effectively makes the My Sites page the default post-login page
			if ( $redirect !== $admin_page ) {
						return $url;
			}
		}

		if ( $user && ! is_wp_error( $user ) ) {
			$user_blogs = get_blogs_of_user( $user->ID );

			// Use current site, if it's there
			foreach ( $user_blogs as $blog ) {
				if ( strpos( $url, $blog->domain ) !== false ) {
					$url = get_admin_url( $blog->userblog_id, 'my-sites.php' );
					return $url;
				}
			}

			// Otherwise, use the first blog found
			$first_blog = reset( $user_blogs ); // Gets the first element of an array, or false if empty
			if ( $first_blog ) {
				$url = get_admin_url( $first_blog->userblog_id, 'my-sites.php' );
				return $url;
			}
		}

		return $url;

	}

	/**
	 * Required for the default post-login screen adjustment above.
	 * If we don't do this, we aren't allowed to redirect to different subdomains.
	 */
	public static function filter_allowed_redirect_hosts( $hosts ) {
		$sites = get_sites();
		foreach ( $sites as $site ) {
			$hosts[] = $site->domain;
		}
		return $hosts;
	}

	/**
	 * We don't want to show the network site on the My Sites screen.
	 */
	public static function filter_out_main_blog_on_my_sites( $sites ) {
		if ( function_exists( 'get_current_screen' ) ) {
			$screen = get_current_screen();
			if ( $screen && $screen->id === 'my-sites' ) {
				if ( ! empty( $sites ) ) {
					$sites = array_filter( $sites, 'static::main_site_filter' );
				}
			}
		}
		return $sites;
	}

	/**
	 * Used by filter_out_main_blog_on_my_sites
	 */
	// TODO: Get the root site programatically - it won't always be 1
	private static function main_site_filter( $site ) {
		if ( 1 === $site->userblog_id ) {
			return false;
		}
		return true;
	}

	/**
	 * Change the text in the admin on 'My Sites' page when no sites are found
	 */
	public static function no_sites_text_filter( $translated_text, $untranslated_text, $domain ) {
		if ( $untranslated_text == 'You must be a member of at least one site to use this page.' ) {
			$sign_up_url     = esc_url( apply_filters( 'wp_signup_location', network_site_url( 'wp-signup.php' ) ) );
			$sign_up_link    = "<a href=\"$sign_up_url\">Create a site</a>";
			$translated_text = __( "You currently have no sites. $sign_up_link to get started." );
		}
		return $translated_text;
	}

}

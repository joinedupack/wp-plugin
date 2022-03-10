<?php

// =====================================================================
// EDUPACK CORE FUNCTIONS
// =====================================================================
// Functions for Edupack Network themes
// =====================================================================

/**
 * Returns a button with a link to either the signup/login page or
 * the dashboard page, depending on whether the user is logged in.
 */
function edupack_signup_or_dashboard_button( $class = 'edupack_core_functions-edupack_signup_or_dashboard_button' ) {
	if ( is_user_logged_in() ) {
		$link_text  = 'My Sites';
		$user_blogs = get_blogs_of_user( get_current_user_id() );
		$first_blog = reset( $user_blogs ); // Gets the first element of an array, or false if empty
		if ( $first_blog ) {
			$link_url = get_admin_url( $first_blog->userblog_id, 'my-sites.php' );
		}
	} else {
		$link_text = 'Signup + Login';
		$link_url  = wp_login_url();
	}
	?>
	<a class="<?php echo $class; ?>" href="<?php echo $link_url; ?>"><?php echo $link_text; ?></a>
	<?php
}

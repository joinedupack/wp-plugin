<?php
/**
 * Copied from wp-singup.php, with some modifications.
 * We only need to worry about signing already authenticated users up for new sites.
 * Send unauthenticated users to the home page, or maybe the regular signup page.
 *
 * This page functions as the onboarding experience for creating a new site from a template
 * in the Edupack Multisite system.
 *
 * @package Edupack
 */

add_action( 'wp_head', 'wp_no_robots' );

nocache_headers();

if ( ! is_user_logged_in() ) {
	wp_redirect( wp_registration_url() );
	die();
}

if ( is_array( get_site_option( 'illegal_names' ) ) && isset( $_GET['new'] ) && in_array( $_GET['new'], get_site_option( 'illegal_names' ) ) ) {
	wp_redirect( network_home_url() );
	die();
}

/**
 * Prints signup_header via wp_head
 *
 * @since MU (3.0.0)
 */
function do_signup_header() {
	/**
	 * Fires within the head section of the site sign-up screen.
	 *
	 * @since 3.0.0
	 */
	do_action( 'signup_header' );
}
add_action( 'wp_head', 'do_signup_header' );

if ( ! is_multisite() ) {
	wp_redirect( wp_registration_url() );
	die();
}

if ( ! is_main_site() ) {
	wp_redirect( network_site_url( 'wp-signup.php' ) );
	die();
}

// Fix for page title
$wp_query->is_404 = false;

/**
 * Fires before the Site Signup page is loaded.
 *
 * @since 4.4.0
 */
do_action( 'before_signup_header' );
get_header( 'wp-signup' );
?>

<div class="edupack-templater">

<?php
/**
 * Fires before the site sign-up form.
 *
 * @since 3.0.0
 */
do_action( 'before_signup_form' );
?>
<div class="templater-signup" id="signup-content">
	<div class="signup-instructions">
		<h2 class="instructions-title">Build Your Site</h2>
		<h3 class="instructions-body">Enter the details of your new Edupack Site.</h3>
	</div>
<?php
/**
 * Generates and displays the Signup and Create Site forms
 *
 * @since MU (3.0.0)
 *
 * @param string          $blogname   The new site name.
 * @param string          $blog_title The new site title.
 * @param WP_Error|string $errors     A WP_Error object containing existing errors. Defaults to empty string.
 */
function show_blog_form( $blogname = '', $blog_title = '', $errors = '' ) {
	if ( ! is_wp_error( $errors ) ) {
		$errors = new WP_Error();
	}

	$current_network = get_network();

	?>
	<!-- Templates -->
	<div class="setupform-template">
		<label class="template-label" for="template-select"><?php _e( 'Site Template' ); ?></label>
	<select class="template-select" name="template-select" id="template-select">
		<option value="none" disabled><?php _e( 'Select template...', 'edupack-text_domain' ); ?></option>
		<?php
			$templates = TemplateFunctions::get_templates();
			foreach ( $templates as $template ) :
			$data_url       = esc_attr( get_site_url( $template->blog_id ) . '?edupack_template_preview=true' );
			$template_name  = esc_attr( get_blog_details( $template->blog_id )->blogname );
			$template_value = esc_attr( $template->blog_id );
			?>
			<option value="<?php echo $template_value; ?>" data-url="<?php echo $data_url; ?>"><?php echo $template_name; ?></option>
		<?php endforeach; ?>
	</select>
	</div>
	<?php

	// Blog name.
	if ( ! is_subdomain_install() ) {
		echo '<label class="setupform-label" for="blogname">' . __( 'Site Name:' ) . '</label>';
	} else {
		echo '<label class="setupform-label" for="blogname">' . __( 'Site Domain' ) . '</label>';
	}

	$errmsg = $errors->get_error_message( 'blogname' );
	if ( $errmsg ) {
		?>
		<div class="setupform-error"><?php echo $errmsg; ?></div>
		<?php
	}

	if ( ! is_subdomain_install() ) {
		echo '<div class="setupform-group"><span class="group-prefix">' . $current_network->domain . $current_network->path . '</span><input class="group-input_text" name="blogname" type="text" id="blogname" value="' . esc_attr( $blogname ) . '" maxlength="60" /></div>';
	} else {
		$site_domain = preg_replace( '|^www\.|', '', $current_network->domain );
		echo '<div class="setupform-group"><input class="group-input_text" name="blogname" type="text" id="blogname" value="' . esc_attr( $blogname ) . '" maxlength="60" /><span class="group-suffix">.' . esc_html( $site_domain ) . '</span></div>';
	}

	if ( ! is_user_logged_in() ) {
		if ( ! is_subdomain_install() ) {
			$site = $current_network->domain . $current_network->path . __( 'sitename' );
		} else {
			$site = __( 'domain' ) . '.' . $site_domain . $current_network->path;
		}

		/* translators: %s: site address */
		echo '<p>(<strong>' . sprintf( __( 'Your address will be %s.' ), $site ) . '</strong>) ' . __( 'Must be at least 4 characters, letters and numbers only. It cannot be changed, so choose carefully!' ) . '</p>';
	}

	// Blog Title
	?>
	<label class="setupform-label" for="blog_title"><?php _e( 'Site Title' ); ?></label>
	<?php
	$errmsg = $errors->get_error_message( 'blog_title' );
	if ( $errmsg ) {
		?>
		
		<div class="setupform-error">

			<?php echo $errmsg; ?>
		
		</div>

		<?php
	}
	echo '<input class="setupform-text_input" name="blog_title" type="text" id="blog_title" value="' . esc_attr( $blog_title ) . '" />';
	?>

	<?php
	// Site Language.
	$languages = signup_get_available_languages();

	if ( ! empty( $languages ) ) :
		?>

			<label class="setupform-label" for="site-language"><?php _e( 'Site Language' ); ?></label>
			<?php
			// Network default.
			$lang = get_site_option( 'WPLANG' );

			if ( isset( $_POST['WPLANG'] ) ) {
				$lang = $_POST['WPLANG'];
			}

			// Use US English if the default isn't available.
			if ( ! in_array( $lang, $languages ) ) {
				$lang = '';
			}

			wp_dropdown_languages(
				array(
					'name'                        => 'WPLANG',
					'id'                          => 'site-language',
					'selected'                    => $lang,
					'languages'                   => $languages,
					'show_available_translations' => false,
				)
			);

	endif; // Languages.

		$blog_public_on_checked  = '';
		$blog_public_off_checked = '';
	if ( isset( $_POST['blog_public'] ) && '0' == $_POST['blog_public'] ) {
		$blog_public_off_checked = 'checked="checked"';
	} else {
		$blog_public_on_checked = 'checked="checked"';
	}
	?>

	<div id="privacy" class="setupform-privacy" hidden>
		<p class="privacy-intro">
			<fieldset>
				<legend><?php _e( 'Privacy:' ); ?></legend>
				<span><?php _e( 'Allow search engines to index this site.' ); ?></span>
				<br style="clear:both" />
				<label class="checkbox" for="blog_public_on">
					<input type="radio" id="blog_public_on" name="blog_public" value="1" <?php echo $blog_public_on_checked; ?> />
					<strong><?php _e( 'Yes' ); ?></strong>
				</label>
				<label class="checkbox" for="blog_public_off">
					<input type="radio" id="blog_public_off" name="blog_public" value="0" <?php echo $blog_public_off_checked; ?> />
					<strong><?php _e( 'No' ); ?></strong>
				</label>
			</fieldset>
		</p>
	</div>

	<div id="edupack-fields" class="setupform-edupack_fields">

		<!-- Tagline -->
		<label class="edupack_fields-label" for="site_tagline"><?php _e( 'Site Tagline' ); ?></label>
		<input class="edupack_fields-text_input" name="site_tagline" type="text" id="site_tagline" value="" maxlength="60">

		<!-- Keywords -->
		<?php // TODO: make this programmatically fetch from allowed keywords ?>
		<label class="edupack_fields-label" for="site_keywords"><?php _e( 'Site Keywords' ); ?></label>
		<select id="site_keywords" class="edupack_fields-site_keywords" name="site_keywords[]" data-type="select-multiple" maxlength="100" multiple>
			<option value="Medical School">Medical School</option>
			<option value="Lab">Lab</option>
			<option value="Event">Event</option>
			<option value="Student Organization">Student Organization</option>
			<option value="Blog">Blog</option>
			<option value="Faculty Profile">Faculty Profile</option>	
		</select>
	</div>

	<!-- Acceptable Use Policy -->
	<div class="edupack_fields-acceptable_use" style="padding: 16px 0;">
		<input type="checkbox" name="acceptable_use" id="acceptable_use" style="display: inline; width: unset;">
		<label for="acceptable_use" style="display: inline;">I have read and agree to the terms & conditions of Edupack's <a href="https://edupack.dev/terms-of-service/" target="_blank">Terms of Service</a>.</label>
	</div>

	<?php
	/**
	 * Fires after the site sign-up form.
	 *
	 * @since 3.0.0
	 *
	 * @param WP_Error $errors A WP_Error object possibly containing 'blogname' or 'blog_title' errors.
	 */
	do_action( 'signup_blogform', $errors );
}

/**
 * Validate the new site signup
 *
 * @since MU (3.0.0)
 *
 * @return array Contains the new site data and error messages.
 */
function validate_blog_form() {
	$user = '';
	if ( is_user_logged_in() ) {
		$user = wp_get_current_user();
	}

	return wpmu_validate_blog_signup( $_POST['blogname'], $_POST['blog_title'], $user );
}

/**
 * Display user registration form
 *
 * @since MU (3.0.0)
 *
 * @param string          $user_name  The entered username.
 * @param string          $user_email The entered email address.
 * @param WP_Error|string $errors     A WP_Error object containing existing errors. Defaults to empty string.
 */
function show_user_form( $user_name = '', $user_email = '', $errors = '' ) {
	if ( ! is_wp_error( $errors ) ) {
		$errors = new WP_Error();
	}

	// User name
	echo '<label for="user_name">' . __( 'Username:' ) . '</label>';
	$errmsg = $errors->get_error_message( 'user_name' );
	if ( $errmsg ) {
		echo '<div class="esetupform-error">' . $errmsg . '</div>';
	}
	echo '<input name="user_name" type="text" id="user_name" value="' . esc_attr( $user_name ) . '" autocapitalize="none" autocorrect="off" maxlength="60" /><br />';
	_e( '(Must be at least 4 characters, letters and numbers only.)' );
	?>

	<label for="user_email"><?php _e( 'Email&nbsp;Address:' ); ?></label>
	<?php
	$errmsg = $errors->get_error_message( 'user_email' );
	if ( $errmsg ) {
		?>
		<p class="error"><?php echo $errmsg; ?></p>
	<?php } ?>
	<input name="user_email" type="email" id="user_email" value="<?php echo esc_attr( $user_email ); ?>" maxlength="200" /><br /><?php _e( 'We send your registration email to this address. (Double-check your email address before continuing.)' ); ?>
	<?php
	$errmsg = $errors->get_error_message( 'generic' );
	if ( $errmsg ) {
		echo '<p class="error">' . $errmsg . '</p>';
	}
	/**
	 * Fires at the end of the user registration form on the site sign-up form.
	 *
	 * @since 3.0.0
	 *
	 * @param WP_Error $errors A WP_Error object containing 'user_name' or 'user_email' errors.
	 */
	do_action( 'signup_extra_fields', $errors );
}

/**
 * Validate user signup name and email
 *
 * @since MU (3.0.0)
 *
 * @return array Contains username, email, and error messages.
 */
function validate_user_form() {
	return wpmu_validate_user_signup( $_POST['user_name'], $_POST['user_email'] );
}

/**
 * Allow returning users to sign up for another site
 *
 * @since MU (3.0.0)
 *
 * @param string          $blogname   The new site name.
 * @param string          $blog_title The new site title.
 * @param WP_Error|string $errors     A WP_Error object containing existing errors. Defaults to empty string.
 */
function signup_another_blog( $blogname = '', $blog_title = '', $errors = '' ) {
	$current_user = wp_get_current_user();

	if ( ! is_wp_error( $errors ) ) {
		$errors = new WP_Error();
	}

	$signup_defaults = array(
		'blogname'   => $blogname,
		'blog_title' => $blog_title,
		'errors'     => $errors,
	);

	/**
	 * Filters the default site sign-up variables.
	 *
	 * @since 3.0.0
	 *
	 * @param array $signup_defaults {
	 *     An array of default site sign-up variables.
	 *
	 *     @type string   $blogname   The site blogname.
	 *     @type string   $blog_title The site title.
	 *     @type WP_Error $errors     A WP_Error object possibly containing 'blogname' or 'blog_title' errors.
	 * }
	 */
	$filtered_results = apply_filters( 'signup_another_blog_init', $signup_defaults );

	$blogname   = $filtered_results['blogname'];
	$blog_title = $filtered_results['blog_title'];
	$errors     = $filtered_results['errors'];

	if ( $errors->has_errors() ) {
		echo '<div class="signup-error" >' . __( 'There was a problem, please correct the form below and try again.' ) . '</div>';
	}
	?>
	<form id="setupform" class="signup-setupform" method="post">

		<input class="setupform-hidden" type="hidden" name="stage" value="gimmeanotherblog" />
	
		<?php
		/**
		 * Hidden sign-up form fields output when creating another site or user.
		 *
		 * @since MU (3.0.0)
		 *
		 * @param string $context A string describing the steps of the sign-up process. The value can be
		 *                        'create-another-site', 'validate-user', or 'validate-site'.
		 */
		do_action( 'signup_hidden_fields', 'create-another-site' );
		?>

		<?php show_blog_form( $blogname, $blog_title, $errors ); ?>

		<input class="setupform-submit disabled" type="submit" name="submit" id="newblog-submit" value="<?php esc_attr_e( 'Create Site' ); ?>" disabled /></p>
	
	</form>
	<?php
}

/**
 * Validate a new site signup for an existing user.
 *
 * @global string          $blogname   The new site's subdomain or directory name.
 * @global string          $blog_title The new site's title.
 * @global WP_Error        $errors     Existing errors in the global scope.
 * @global string          $domain     The new site's domain.
 * @global string          $path       The new site's path.
 *
 * @since MU (3.0.0)
 *
 * @return null|bool True if site signup was validated, false if error.
 *                   The function halts all execution if the user is not logged in.
 */
function validate_another_blog_signup() {
	global $blogname, $blog_title, $errors, $domain, $path;
	$current_user = wp_get_current_user();
	if ( ! is_user_logged_in() ) {
		die();
	}

	$result = validate_blog_form();

	// Extracted values set/overwrite globals.
	$domain     = $result['domain'];
	$path       = $result['path'];
	$blogname   = $result['blogname'];
	$blog_title = $result['blog_title'];
	$errors     = $result['errors'];

	if ( $errors->has_errors() ) {
		signup_another_blog( $blogname, $blog_title, $errors );
		return false;
	}

	$public = (int) $_POST['blog_public'];

	$blog_meta_defaults = array(
		'lang_id' => 1,
		'public'  => $public,
	);

	// Handle the language setting for the new site.
	if ( ! empty( $_POST['WPLANG'] ) ) {

		$languages = signup_get_available_languages();

		if ( in_array( $_POST['WPLANG'], $languages ) ) {
			$language = wp_unslash( sanitize_text_field( $_POST['WPLANG'] ) );

			if ( $language ) {
				$blog_meta_defaults['WPLANG'] = $language;
			}
		}
	}

	/**
	 * Filters the new site meta variables.
	 *
	 * Use the {@see 'add_signup_meta'} filter instead.
	 *
	 * @since MU (3.0.0)
	 * @deprecated 3.0.0 Use the {@see 'add_signup_meta'} filter instead.
	 *
	 * @param array $blog_meta_defaults An array of default blog meta variables.
	 */
	$meta_defaults = apply_filters( 'signup_create_blog_meta', $blog_meta_defaults );

	/**
	 * Filters the new default site meta variables.
	 *
	 * @since 3.0.0
	 *
	 * @param array $meta {
	 *     An array of default site meta variables.
	 *
	 *     @type int $lang_id     The language ID.
	 *     @type int $blog_public Whether search engines should be discouraged from indexing the site. 1 for true, 0 for false.
	 * }
	 */
	$meta = apply_filters( 'add_signup_meta', $meta_defaults );

	$blog_id = wpmu_create_blog( $domain, $path, $blog_title, $current_user->ID, $meta, get_current_network_id() );

	if ( is_wp_error( $blog_id ) ) {
		return false;
	}

	confirm_another_blog_signup( $domain, $path, $blog_title, $current_user->user_login, $current_user->user_email, $meta, $blog_id );
	return true;
}

/**
 * Confirm a new site signup.
 *
 * @since MU (3.0.0)
 * @since 4.4.0 Added the `$blog_id` parameter.
 *
 * @param string $domain     The domain URL.
 * @param string $path       The site root path.
 * @param string $blog_title The site title.
 * @param string $user_name  The username.
 * @param string $user_email The user's email address.
 * @param array  $meta       Any additional meta from the {@see 'add_signup_meta'} filter in validate_blog_signup().
 * @param int    $blog_id    The site ID.
 */
function confirm_another_blog_signup( $domain, $path, $blog_title, $user_name, $user_email = '', $meta = array(), $blog_id = 0 ) {

	if ( $blog_id ) {
		switch_to_blog( $blog_id );
		$home_url  = home_url( '/' );
		$login_url = wp_login_url();
		restore_current_blog();
	} else {
		$home_url  = 'http://' . $domain . $path;
		$login_url = 'http://' . $domain . $path . 'wp-login.php';
	}

	$site = sprintf(
		'<a href="%1$s">%2$s</a>',
		esc_url( $home_url ),
		$blog_title
	);

	?>
	<div class="signup-success">
		<h2 class="success-title">
		<?php
			/* translators: %s: site name */
			printf( __( 'Success!' ), $site );
		?>
		</h2>
		<p class="success-body">
			<?php
			printf(
				/* translators: 1: link to new site, 2: login URL, 3: username */
				__( '%1$s is your new site. <a href="%2$s">Log in</a> as &#8220;%3$s&#8221; using your Edupack password to edit the site.' ),
				sprintf(
					'<a href="%s">%s</a>',
					esc_url( $home_url ),
					untrailingslashit( $domain . $path )
				),
				esc_url( $login_url ),
				$user_name
			);
			?>
		</pack>
	</div>

	<?php
	/**
	 * Fires when the site or user sign-up process is complete.
	 *
	 * @since 3.0.0
	 */
	do_action( 'signup_finished' );
}

/**
 * Setup the new user signup process
 *
 * @since MU (3.0.0)
 *
 * @param string          $user_name  The username.
 * @param string          $user_email The user's email.
 * @param WP_Error|string $errors     A WP_Error object containing existing errors. Defaults to empty string.
 */
function signup_user( $user_name = '', $user_email = '', $errors = '' ) {
	global $active_signup;

	if ( ! is_wp_error( $errors ) ) {
		$errors = new WP_Error();
	}

	$signup_for = isset( $_POST['signup_for'] ) ? esc_html( $_POST['signup_for'] ) : 'blog';

	$signup_user_defaults = array(
		'user_name'  => $user_name,
		'user_email' => $user_email,
		'errors'     => $errors,
	);

	/**
	 * Filters the default user variables used on the user sign-up form.
	 *
	 * @since 3.0.0
	 *
	 * @param array $signup_user_defaults {
	 *     An array of default user variables.
	 *
	 *     @type string   $user_name  The user username.
	 *     @type string   $user_email The user email address.
	 *     @type WP_Error $errors     A WP_Error object with possible errors relevant to the sign-up user.
	 * }
	 */
	$filtered_results = apply_filters( 'signup_user_init', $signup_user_defaults );
	$user_name        = $filtered_results['user_name'];
	$user_email       = $filtered_results['user_email'];
	$errors           = $filtered_results['errors'];

	?>

	<h2>
	<?php
		/* translators: %s: name of the network */
		printf( __( 'Get your own %s account in seconds' ), get_network()->site_name );
	?>
	</h2>
	<form id="setupform" class="signup-setupform" method="post" novalidate="novalidate">
		<input class="setupform-hidden" type="hidden" name="stage" value="validate-user-signup" />
		<?php
		/** This action is documented in wp-signup.php */
		do_action( 'signup_hidden_fields', 'validate-user' );
		?>
		<?php show_user_form( $user_name, $user_email, $errors ); ?>

		<p>
		<?php if ( 'blog' === $active_signup ) { ?>
			<input id="signupblog" type="hidden" name="signup_for" value="blog" />
		<?php } elseif ( 'user' === $active_signup ) { ?>
			<input id="signupblog" type="hidden" name="signup_for" value="user" />
		<?php } else { ?>
			<input id="signupblog" type="radio" name="signup_for" value="blog" <?php checked( $signup_for, 'blog' ); ?> />
			<label class="checkbox" for="signupblog"><?php _e( 'Gimme a site!' ); ?></label>
			<br />
			<input id="signupuser" type="radio" name="signup_for" value="user" <?php checked( $signup_for, 'user' ); ?> />
			<label class="checkbox" for="signupuser"><?php _e( 'Just a username, please.' ); ?></label>
		<?php } ?>
		</p>

		<p class="submit"><input type="submit" name="submit" class="submit" value="<?php esc_attr_e( 'Next' ); ?>" /></p>
	</form>
	<?php
}

/**
 * Validate the new user signup
 *
 * @since MU (3.0.0)
 *
 * @return bool True if new user signup was validated, false if error
 */
function validate_user_signup() {
	$result     = validate_user_form();
	$user_name  = $result['user_name'];
	$user_email = $result['user_email'];
	$errors     = $result['errors'];

	if ( $errors->has_errors() ) {
		signup_user( $user_name, $user_email, $errors );
		return false;
	}

	if ( 'blog' == $_POST['signup_for'] ) {
		signup_blog( $user_name, $user_email );
		return false;
	}

	/** This filter is documented in wp-signup.php */
	wpmu_signup_user( $user_name, $user_email, apply_filters( 'add_signup_meta', array() ) );

	confirm_user_signup( $user_name, $user_email );
	return true;
}

/**
 * New user signup confirmation
 *
 * @since MU (3.0.0)
 *
 * @param string $user_name The username.
 * @param string $user_email The user's email address.
 */
function confirm_user_signup( $user_name, $user_email ) {
	?>
	<h2>
	<?php
	/* translators: %s: username */
	printf( __( '%s is your new username' ), $user_name )
	?>
	</h2>
	<p><?php _e( 'But, before you can start using your new username, <strong>you must activate it</strong>.' ); ?></p>
	<p>
	<?php
	/* translators: %s: email address */
	printf( __( 'Check your inbox at %s and click the link given.' ), '<strong>' . $user_email . '</strong>' );
	?>
	</p>
	<p><?php _e( 'If you do not activate your username within two days, you will have to sign up again.' ); ?></p>
	<?php
	/** This action is documented in wp-signup.php */
	do_action( 'signup_finished' );
}

/**
 * Setup the new site signup
 *
 * @since MU (3.0.0)
 *
 * @param string          $user_name  The username.
 * @param string          $user_email The user's email address.
 * @param string          $blogname   The site name.
 * @param string          $blog_title The site title.
 * @param WP_Error|string $errors     A WP_Error object containing existing errors. Defaults to empty string.
 */
function signup_blog( $user_name = '', $user_email = '', $blogname = '', $blog_title = '', $errors = '' ) {
	if ( ! is_wp_error( $errors ) ) {
		$errors = new WP_Error();
	}

	$signup_blog_defaults = array(
		'user_name'  => $user_name,
		'user_email' => $user_email,
		'blogname'   => $blogname,
		'blog_title' => $blog_title,
		'errors'     => $errors,
	);

	/**
	 * Filters the default site creation variables for the site sign-up form.
	 *
	 * @since 3.0.0
	 *
	 * @param array $signup_blog_defaults {
	 *     An array of default site creation variables.
	 *
	 *     @type string   $user_name  The user username.
	 *     @type string   $user_email The user email address.
	 *     @type string   $blogname   The blogname.
	 *     @type string   $blog_title The title of the site.
	 *     @type WP_Error $errors     A WP_Error object with possible errors relevant to new site creation variables.
	 * }
	 */
	$filtered_results = apply_filters( 'signup_blog_init', $signup_blog_defaults );

	$user_name  = $filtered_results['user_name'];
	$user_email = $filtered_results['user_email'];
	$blogname   = $filtered_results['blogname'];
	$blog_title = $filtered_results['blog_title'];
	$errors     = $filtered_results['errors'];

	if ( empty( $blogname ) ) {
		$blogname = $user_name;
	}
	?>
	<form id="setupform" class="signup-setupform" method="post">
		<input class="setupform-hidden" type="hidden" name="stage" value="validate-blog-signup" />
		<input class="setupform-hidden" type="hidden" name="user_name" value="<?php echo esc_attr( $user_name ); ?>" />
		<input class="setupform-hidden" type="hidden" name="user_email" value="<?php echo esc_attr( $user_email ); ?>" />
		<?php
		/** This action is documented in wp-signup.php */
		do_action( 'signup_hidden_fields', 'validate-site' );
		?>
		<?php show_blog_form( $blogname, $blog_title, $errors ); ?>
		<input class="setupform-submit" type="submit" name="submit" value="<?php esc_attr_e( 'Signup' ); ?>" /></p>
	</form>
	<?php
}

/**
 * Validate new site signup
 *
 * @since MU (3.0.0)
 *
 * @return bool True if the site signup was validated, false if error
 */
function validate_blog_signup() {
	// Re-validate user info.
	$user_result = wpmu_validate_user_signup( $_POST['user_name'], $_POST['user_email'] );
	$user_name   = $user_result['user_name'];
	$user_email  = $user_result['user_email'];
	$user_errors = $user_result['errors'];

	if ( $user_errors->has_errors() ) {
		signup_user( $user_name, $user_email, $user_errors );
		return false;
	}

	$result     = wpmu_validate_blog_signup( $_POST['blogname'], $_POST['blog_title'] );
	$domain     = $result['domain'];
	$path       = $result['path'];
	$blogname   = $result['blogname'];
	$blog_title = $result['blog_title'];
	$errors     = $result['errors'];

	if ( $errors->has_errors() ) {
		signup_blog( $user_name, $user_email, $blogname, $blog_title, $errors );
		return false;
	}

	$public      = (int) $_POST['blog_public'];
	$signup_meta = array(
		'lang_id' => 1,
		'public'  => $public,
	);

	// Handle the language setting for the new site.
	if ( ! empty( $_POST['WPLANG'] ) ) {

		$languages = signup_get_available_languages();

		if ( in_array( $_POST['WPLANG'], $languages ) ) {
			$language = wp_unslash( sanitize_text_field( $_POST['WPLANG'] ) );

			if ( $language ) {
				$signup_meta['WPLANG'] = $language;
			}
		}
	}

	/** This filter is documented in wp-signup.php */
	$meta = apply_filters( 'add_signup_meta', $signup_meta );

	wpmu_signup_blog( $domain, $path, $blog_title, $user_name, $user_email, $meta );
	confirm_blog_signup( $domain, $path, $blog_title, $user_name, $user_email, $meta );
	return true;
}

/**
 * New site signup confirmation
 *
 * @since MU (3.0.0)
 *
 * @param string $domain The domain URL.
 * @param string $path The site root path.
 * @param string $blog_title The new site title.
 * @param string $user_name The user's username.
 * @param string $user_email The user's email address.
 * @param array  $meta Any additional meta from the {@see 'add_signup_meta'} filter in validate_blog_signup().
 */
function confirm_blog_signup( $domain, $path, $blog_title, $user_name = '', $user_email = '', $meta = array() ) {
	?>
	<h2>
	<?php
	/* translators: %s: site address */
	printf( __( 'Congratulations! Your new site, %s, is almost ready.' ), "<a href='http://{$domain}{$path}'>{$blog_title}</a>" )
	?>
	</h2>

	<p><?php _e( 'But, before you can start using your site, <strong>you must activate it</strong>.' ); ?></p>
	<p>
	<?php
	/* translators: %s: email address */
	printf( __( 'Check your inbox at %s and click the link given.' ), '<strong>' . $user_email . '</strong>' );
	?>
	</p>
	<p><?php _e( 'If you do not activate your site within two days, you will have to sign up again.' ); ?></p>
	<h2><?php _e( 'Still waiting for your email?' ); ?></h2>
	<p>
		<?php _e( 'If you haven&#8217;t received your email yet, there are a number of things you can do:' ); ?>
		<ul id="noemail-tips">
			<li><p><strong><?php _e( 'Wait a little longer. Sometimes delivery of email can be delayed by processes outside of our control.' ); ?></strong></p></li>
			<li><p><?php _e( 'Check the junk or spam folder of your email client. Sometime emails wind up there by mistake.' ); ?></p></li>
			<li>
			<?php
				/* translators: %s: email address */
				printf( __( 'Have you entered your email correctly? You have entered %s, if it&#8217;s incorrect, you will not receive your email.' ), $user_email );
			?>
			</li>
		</ul>
	</p>
	<?php
	/** This action is documented in wp-signup.php */
	do_action( 'signup_finished' );
}

/**
 * Retrieves languages available during the site/user signup process.
 *
 * @since 4.4.0
 *
 * @see get_available_languages()
 *
 * @return array List of available languages.
 */
function signup_get_available_languages() {
	/**
	 * Filters the list of available languages for front-end site signups.
	 *
	 * Passing an empty array to this hook will disable output of the setting on the
	 * signup form, and the default language will be used when creating the site.
	 *
	 * Languages not already installed will be stripped.
	 *
	 * @since 4.4.0
	 *
	 * @param array $available_languages Available languages.
	 */
	$languages = (array) apply_filters( 'signup_get_available_languages', get_available_languages() );

	/*
	 * Strip any non-installed languages and return.
	 *
	 * Re-call get_available_languages() here in case a language pack was installed
	 * in a callback hooked to the 'signup_get_available_languages' filter before this point.
	 */
	return array_intersect_assoc( $languages, get_available_languages() );
}

// Main
$active_signup = get_site_option( 'registration', 'none' );

/**
 * Filters the type of site sign-up.
 *
 * @since 3.0.0
 *
 * @param string $active_signup String that returns registration type. The value can be
 *                              'all', 'none', 'blog', or 'user'.
 */
$active_signup = apply_filters( 'wpmu_active_signup', $active_signup );

$newblogname = isset( $_GET['new'] ) ? strtolower( preg_replace( '/^-|-$|[^-a-zA-Z0-9]/', '', $_GET['new'] ) ) : null;

$current_user = wp_get_current_user();
if ( 'none' === $active_signup ) {
	_e( 'Registration has been disabled.' );
} elseif ( 'blog' === $active_signup && ! is_user_logged_in() ) {
	$login_url = wp_login_url( network_site_url( 'wp-signup.php' ) );
	/* translators: %s: login URL */
	printf( __( 'You must first <a href="%s">log in</a>, and then you can create a new site.' ), $login_url );
} else {
	$stage = isset( $_POST['stage'] ) ? $_POST['stage'] : 'default';
	switch ( $stage ) {
		case 'gimmeanotherblog':
			validate_another_blog_signup();
			break;
		case 'default':
		default:
			$user_email = isset( $_POST['user_email'] ) ? $_POST['user_email'] : '';
			/**
			 * Fires when the site sign-up form is sent.
			 *
			 * @since 3.0.0
			 */
			do_action( 'preprocess_signup_form' );
			if ( is_user_logged_in() && ( 'all' === $active_signup || 'blog' === $active_signup ) ) {
				signup_another_blog( $newblogname );
			} elseif ( ! is_user_logged_in() && ( 'blog' === $active_signup ) ) {
				_e( 'Sorry, new registrations are not allowed at this time.' );
			}

			if ( $newblogname ) {
				$newblog = get_blogaddress_by_name( $newblogname );

				if ( 'blog' === $active_signup || 'all' === $active_signup ) {
					printf(
						/* translators: %s: site address */
						'<p><em>' . __( 'The site you were looking for, %s, does not exist, but you can create it now!' ) . '</em></p>',
						'<strong>' . $newblog . '</strong>'
					);
				} else {
					printf(
						/* translators: %s: site address */
						'<p><em>' . __( 'The site you were looking for, %s, does not exist.' ) . '</em></p>',
						'<strong>' . $newblog . '</strong>'
					);
				}
			}
			break;
	}
}
?>
</div>
<div class="templater-preview" id="templater-preview" data-viewport="desktop">
	<div class="preview-loading_container" id="template-loading-container" hidden>
		<div class="loading_container-content" id="loading-image-container">
			<img class="content-image" alt="loading..." src="<?php echo $GLOBALS['edupack_variables']['url'] . 'assets/img/loading.gif'; ?>" />    
		</div>
	</div>

	<div class="preview-selector">
		<div class="selector-desktop" id="selector-desktop"><svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="desktop" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512" class="svg-inline--fa fa-desktop fa-w-18 fa-3x"><path fill="currentColor" d="M528 0H48C21.5 0 0 21.5 0 48v320c0 26.5 21.5 48 48 48h192l-16 48h-72c-13.3 0-24 10.7-24 24s10.7 24 24 24h272c13.3 0 24-10.7 24-24s-10.7-24-24-24h-72l-16-48h192c26.5 0 48-21.5 48-48V48c0-26.5-21.5-48-48-48zm-16 352H64V64h448v288z" class=""></path></svg></div>
		<div class="selector-tablet" id="selector-tablet"><svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="tablet-alt" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" class="svg-inline--fa fa-tablet-alt fa-w-14 fa-3x"><path fill="currentColor" d="M400 0H48C21.5 0 0 21.5 0 48v416c0 26.5 21.5 48 48 48h352c26.5 0 48-21.5 48-48V48c0-26.5-21.5-48-48-48zM224 480c-17.7 0-32-14.3-32-32s14.3-32 32-32 32 14.3 32 32-14.3 32-32 32zm176-108c0 6.6-5.4 12-12 12H60c-6.6 0-12-5.4-12-12V60c0-6.6 5.4-12 12-12h328c6.6 0 12 5.4 12 12v312z" class=""></path></svg></div>
		<div class="selector-mobile" id="selector-mobile"><svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="mobile-alt" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512" class="svg-inline--fa fa-mobile-alt fa-w-10 fa-3x"><path fill="currentColor" d="M272 0H48C21.5 0 0 21.5 0 48v416c0 26.5 21.5 48 48 48h224c26.5 0 48-21.5 48-48V48c0-26.5-21.5-48-48-48zM160 480c-17.7 0-32-14.3-32-32s14.3-32 32-32 32 14.3 32 32-14.3 32-32 32zm112-108c0 6.6-5.4 12-12 12H60c-6.6 0-12-5.4-12-12V60c0-6.6 5.4-12 12-12h200c6.6 0 12 5.4 12 12v312z" class=""></path></svg></div>
	</div>
	
	<div class="preview-viewport">
		<div class="viewport-header">
			<div class="header-address">Your new website</div>
		</div>
		<iframe class="viewport-frame" id="template-iframe" src="" frameborder="0" hidden></iframe>
	</div>
</div>
<?php
/**
 * Fires after the sign-up forms, before wp_footer.
 *
 * @since 3.0.0
 */
do_action( 'after_signup_form' );
?>

</div>

<?php
get_footer( 'wp-signup' );
?>

</div>

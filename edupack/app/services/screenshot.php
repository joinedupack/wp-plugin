<?php


/**
 * Save a screenshot of the site homepage, whenever the homepage is updated
 *
 * Uses Browsershot: https://browshot.com/
 */
function edupack_update_homepage_screenshot( $blog_id = null ) {
	// We only need this here, so let's keep it simple
	if ( defined( 'BROWSERSHOT_API_KEY' ) ) {

		require_once 'lib/Browshot.php';
		$browshot = new Browshot( BROWSERSHOT_API_KEY );

		if ( ! $blog_id ) {
			$blog_id = get_current_blog_id();
		}

		// error_log("SCREENSHOT: Using site id: " . $blog_id);

		$blog_url  = get_site_url( $blog_id );
		$blog_name = get_bloginfo( 'name' );

		// Where to save?
		$uploads     = wp_upload_dir();
		$upload_path = $uploads['basedir'];
		$upload_path = sprintf( '%s/screenshots', $upload_path );

		// Make sure our target directory exists, just in case
		if ( ! file_exists( $upload_path ) ) {
			mkdir( $upload_path );
		}

		$file = sprintf( '%s/%u.png', $upload_path, $blog_id );

		// If the front page has a featured image, use that instead of a screenshot
		switch_to_blog( $blog_id );
		$frontpage_id = get_option( 'page_on_front' );
		restore_current_blog();

		$image_id = get_post_thumbnail_id( $frontpage_id );
		// error_log("Found Image ID: " . $image_id);

		if ( $image_id ) {
			$path = get_attached_file( $image_id );
			// error_log("Full image path: " . $path);

			if ( $path ) {
				$editor = wp_get_image_editor( $path );
				$editor->resize( 624, 375 );
				$editor->save( $file );
				// error_log("Saved file as: " . $file);
				return;
			}
		}

		// error_log("SCREENSHOT: Using homepage URL: " . $blog_url);
		// error_log("SCREENSHOT: Saving file to: " . $file);

		$info = $browshot->simple_file(
			$file,
			array(
				'url'         => $blog_url,
				'width'       => 624,
				'height'      => 375,
				'cache'       => 0,
				'instance_id' => 65,
				'delay'       => 3,
				'max_wait'    => 3,
			)
			);
		$dump = '';
		if ( $info && is_array( $info ) ) {
			foreach ( $info as $k => $v ) {
				$dump .= sprintf( '%s => %s; ', $k, $v );
			}
		}

		$file_stat = stat( $file );
		$file_info = print_r( $file_stat, true );
		// error_log("SCREENSHOT: Browshot Response: " . $dump);
		// error_log("SCREENSHOT: File Info: " . $file_info);
	} else {
		error_log( 'Missing BROWSERSHOT_API_KEY' );
	}
}

/**
 * Loop through all published sites and generate screenshots where needed
 */
function edupack_update_published_screenshots() {
	$all_sites = get_sites(
		array(
			'public' => 1,
			'number' => 9999,
		)
	);
	foreach ( $all_sites as $site ) {
		switch_to_blog( $site->blog_id );

		// Find the saved screenshot, if there is one
		$uploads = wp_upload_dir();
		// Subdomain install handles paths differently than non-subdomain
		if ( defined( 'SUBDOMAIN_INSTALL' ) && ! SUBDOMAIN_INSTALL ) {
			$uploads['basedir'] = preg_replace( '/\/sites\/\d+/', '', $uploads['basedir'] );
		}
		$file = sprintf( '%s/screenshots/%u.png', $uploads['basedir'], $site->blog_id );

		// error_log("SCREENSHOT CRON:Looking for screenshot file: " . $file);

		// If there is a screenshot, check its age
		if ( file_exists( $file ) ) {
			$file_info        = stat( $file );
			$file_mdate       = $file_info['mtime'];
			$file_pretty_date = date( 'Y M D H:i:s', $file_mdate );

			$post_id          = get_option( 'page_on_front' );
			$home             = get_post( $post_id );
			$post_mdate       = strtotime( $home->post_modified );
			$post_pretty_date = date( 'Y M D H:i:s', $post_mdate );

			// error_log("SCREENSHOT CRON:File timestamp: " . $file_mdate);
			// error_log("SCREENSHOT CRON:File date: " . $file_pretty_date);
			// error_log("SCREENSHOT CRON:Post timestamp: " . $post_mdate);
			// error_log("SCREENSHOT CRON:Post date: " . $post_pretty_date);

			// If the homepage last modified is more recent than the screenshot create a new one
			if ( $post_mdate > $file_mdate ) {
				// error_log("SCREENSHOT CRON:Post is newer than screenshot, recreating");
				$blog_id = get_current_blog_id();
				edupack_update_homepage_screenshot( $blog_id );
			} else {
				// error_log("SCREENSHOT CRON:Screenshot is more recent than post update, skipping");
			}
		} else { // Otherwise create one
			$blog_id = get_current_blog_id();
			// error_log("SCREENSHOT CRON:No screenshot, creating one for " . $blog_id);
			edupack_update_homepage_screenshot( $blog_id );
		}

		restore_current_blog();
	}
}
add_action( 'edupack_update_screenshots', 'edupack_update_published_screenshots' );

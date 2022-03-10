<?php
/**
 * Edupack
 *
 * Base class for edupack services and controllers
 *
 * @package edupack
 * @version 1.0.0
 */

/**
 * If already exists - return can act as file handler.
 */
if ( class_exists( 'Edupack' ) ) {
	return;
}

class Edupack {
	public function __construct() {}

	/**
	 * Returns the path to the plugin
	 * 
	 * @return string
	 */
	public function get_plugin_path() {
		return EDUPACK_PATH;
	}

	/**
	 * Returns the url to the plugin
	 * 
	 * @return string
	 */
	public function get_plugin_url() {
		return EDUPACK_URL;
	}

	/**
	 * Return the entire css dist path for filename
	 * 
	 * @return string
	 */
	public function get_dist_css_path( $filename ) {
		return $this->get_plugin_path() . 'assets/dist/css/' . $filename;
	}

	/**
	 * Return the entire css dist url for filename
	 * 
	 * @return string
	 */
	public function get_dist_css_url( $filename ) {
		return $this->get_plugin_url() . 'assets/dist/css/' . $filename;
	}

	/**
	 * Return the entire css dist path for filename
	 * 
	 * @return string
	 */
	public function get_dist_js_path( $filename ) {
		return $this->get_plugin_path() . 'assets/dist/js/' . $filename;
	}

	 /**
	 * Return the entire css dist url for filename
	 * 
	 * @return string
	 */
	public function get_dist_js_url( $filename ) {
		return $this->get_plugin_url() . 'assets/dist/js/' . $filename;
	}

	/**
	 * Require the admin template from given filename
	 * 
	 * @return void
	 */
	public function include_admin_template( $filename ) {
		require_once $this->get_plugin_path() . 'templates/admin/' . $filename;
	}

	/**
	 * Get the asset path
	 * 
	 * @return void
	 */
	public function get_asset_path( $filename ) {
		return $this->get_plugin_path() . 'assets/img/' . $filename;
	}

	/**
	 * Get the asset url
	 * 
	 * @return void
	 */
	public function get_asset_url( $filename ) {
		return $this->get_plugin_url() . '/assets/img/' . $filename;
	}

	/**
	 * Get the path to templates
	 */
	public function get_admin_template_path( $filename = '' ) {
		return $this->get_plugin_path() . '/templates/admin' . $filename;
	}

	/**
	 * Get value from the $_POST request
	 */
	public function get_post_field( $field_name, $sanitize = false ) {
		$value = $_POST[ $field_name ] ?? '';

		if ( false === $sanitize ) {
			return $value;
		}

		if ( is_string( $value ) ) {
			return sanitize_text_field( $value );
		}

		return $value;
	}

	/**
	 * Var dump, pretty print & die, and remove dump caps.
	 *
	 * @param mixed $value The value that you want to output.
	 * @param bool  $die Should we kill the page.
	 */
	public function edu_die( $value, $die = true ) {
		ini_set( 'xdebug.var_display_max_depth', '-1' );
		ini_set( 'xdebug.var_display_max_children', '-1' );
		ini_set( 'xdebug.var_display_max_data', '-1' );
		echo '<pre>';
		var_dump( $value );
		echo '</pre>';

		if ( $die ) :
			die();
		endif;
	}

	/**
	 * Get settings group or the individual option
	 * 
	 * @param string $group - The settings group
	 * @param string $key - The sttings key
	 * 
	 * @return mixed
	 */
	public function get_setting_option( $group, $key = '' ) {
		$options = get_network_option( false, $group );

		if ( ! $key ) {
			return $options;
		}

		return $options[ $key ] ?? '';
	}

	/**
	 * Echo selected
	 * 
	 * @param string $current_value - The current settings value
	 * @param string $key - The field value
	 * 
	 * @return mixed
	 */
	public function is_selected( $current_value, $field_value ) {
		if ( $current_value === $field_value ) {
			echo 'selected';
		}
	}

	/**
	 * Echo checked
	 * 
	 * @param string $current_value - The current settings value
	 * @param string $key - The field value
	 * 
	 * @return mixed
	 */
	public function is_checked( $current_value, $field_value ) {
		if ( $current_value === $field_value ) {
			echo 'checked';
		}
	}

	/**
	 * Echo checked
	 * 
	 * @param int $blog_id - The blog ID
	 * 
	 * @return mixed
	 */
	public function get_blog_title( $blog_id ) {
		switch_to_blog( $blog_id );
		$site_title = get_bloginfo( 'name' );
		restore_current_blog();

		return $site_title;
	}

	/**
	 * Get admin URL
	 * 
	 * @param int $blog_id - The blog ID
	 * 
	 * @return mixed
	 */
	public function get_admin_url( $blog_id ) {
		switch_to_blog( $blog_id );
		$site_url = admin_url();
		restore_current_blog();

		return $site_url;
	}

	/**
	 * Returns a base64 URL for the svg for use in the menu.
	 *
	 * @since 3.3.0
	 *
	 * @param bool $base64 Whether or not to return base64'd output.
	 *
	 * @return string
	 */
	public static function get_icon_svg( $base64 = true ) {
		// _deprecated_function( __METHOD__, 'WPSEO 15.5' );

		$svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 332 332"><defs><style>.cls-1{fill:#9ea3a8;}</style></defs><g id="Layer_2" data-name="Layer 2"><g id="Layer_1-2" data-name="Layer 1"><path class="cls-1" d="M166,0A166,166,0,1,0,332,166,166,166,0,0,0,166,0Zm-4.53,246.27H80.65V182.43h80.82Zm.14-96.64H80.8V85.73h80.81Zm76.1-1c-15.25,1.27-30.69.28-46.67.28V86.49c.32-.27.6-.71.87-.7,15.19.18,30.46-.45,45.56.85C252.73,88,263.9,102,263.94,117.53S253,147.33,237.71,148.59Z"/></g></g></svg>';

		if ( $base64 ) {
			//phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- This encoding is intended.
			return 'data:image/svg+xml;base64,' . base64_encode( $svg );
		}

		return $svg;
	}

	/*
	 * Get admin URL
	 *
	 * @param string $ts1
	 * @param string $ts2
	 *
	 * @return mixed
	 */
	public function timestamp_diff( $ts1, $ts2 ) {
		$ts1_C = date_create( $ts1 );
		$ts2_C = date_create( $ts2 );
		$interval = date_diff( $ts1_C, $ts2_C );
		$diff = $interval->format('%a days');

		return $diff;
	}

	/**
	 * Convert arrays into multiple files inside of a zip.
	 */
	public function array_to_zipped_csv_download( $arrays, $filename = 'export.zip', $csv_delimiter = ',' ) {
		$zip = new ZipArchive;
		$zip->open( $filename, ZipArchive::CREATE );
		foreach ( $arrays as $key => $array ) {
			$output_handle = fopen( 'php://temp/maxmemory:1048576', 'w' );
			if ( ! $output_handle ) {
				die( 'Failed to create temporary file' );
			}
			foreach ( $array as $line ) {
				fputcsv( $output_handle, $line, $csv_delimiter );
			}
			rewind( $output_handle );
			$zip->addFromString( 'file-' . $key . '.csv', stream_get_contents( $output_handle ) );
			fclose( $output_handle );
		}
		$zip->close();
		header( 'Content-Type: application/zip' );
		header( 'Content-disposition: attachment; filename=' . $filename );
		header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
		header( 'Content-Description: File Transfer' );
		header( 'Expires: 0' );
		header( 'Pragma: public' );
		header( 'Content-Length: ' . filesize( $filename ) );
		readfile( $filename );
		unlink( $filename );
	}

	/**
	 * Create a downloadable csv from an array of data.
	 *
	 * @param array  $array The array to convert.
	 * @param string $filename The name given to the downloadable file.
	 * @param string $delimiter The seperator.
	 */
	function array_to_csv_download( $array, $filename = 'export.csv', $delimiter = ',' ) {

		$output_handle = fopen( 'php://output', 'w' );

		header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
		header( 'Content-Description: File Transfer' );
		header( 'Content-type: text/csv' );
		header( 'Content-Disposition: attachment; filename=' . $filename );
		header( 'Expires: 0' );
		header( 'Pragma: public' );

		foreach ( $array as $line ) {
			fputcsv( $output_handle, $line, $delimiter );
		}

		fclose( $output_handle );
		exit();
	}

	/**
	 * Get the current url minus REQUEST_URI
	 */
	public function url_origin( $s, $use_forwarded_host = false ) {
		$ssl      = ( ! empty( $s['HTTPS'] ) && 'on' === $s['HTTPS'] );
		$sp       = strtolower( $s['SERVER_PROTOCOL'] );
		$protocol = substr( $sp, 0, strpos( $sp, '/' ) ) . ( ( $ssl ) ? 's' : '' );
		$port     = $s['SERVER_PORT'];
		$port     = ( ( ! $ssl && '80' === $port ) || ( $ssl && '443' === $port ) ) ? '' : ':' . $port;
		$host     = ( $use_forwarded_host && isset( $s['HTTP_X_FORWARDED_HOST'] ) ) ? $s['HTTP_X_FORWARDED_HOST'] : ( isset( $s['HTTP_HOST'] ) ? $s['HTTP_HOST'] : null );
		$host     = isset( $host ) ? $host : $s['SERVER_NAME'] . $port;
		return $protocol . '://' . $host;
	}

	/**
	 * Get the full current url.
	 */
	public function full_url( $s, $use_forwarded_host = false ) {
		return $this->url_origin( $s, $use_forwarded_host ) . $s['REQUEST_URI'];
	}
}

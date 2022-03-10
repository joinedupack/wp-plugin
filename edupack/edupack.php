<?php
/**
 * Plugin Name: Edupack
 * Plugin URI: https://edupack.dev
 * Description: Bring order to your university websites, no matter where they are. Control and optimize every campus site.
 * Version: 0.0.3
 * Author: Edupack
 * Author URI: https://edupack.dev
 * License: GPL2+
 *
 * @package edupack/edupack
 */
define( 'EDUPACK_URL', plugin_dir_url( __FILE__ ) );
define( 'EDUPACK_PATH', plugin_dir_path( __FILE__ ) );


define( 'EDUPACK_URL', plugin_dir_url( __FILE__ ) );
define( 'EDUPACK_PATH', plugin_dir_path( __FILE__ ) );


$edupack_path = WP_PLUGIN_DIR . '/edupack';

function edupack_get_class( $filename ) {
	return EDUPACK_PATH . '/app/' . $filename;
}

/**
 * CUSTOM GLOBAL VARIABLES
 */
function edupack_global_vars() {

	global $edupack_variables;
	$edupack_variables = array(

		'url'  => plugin_dir_url( __FILE__ ),
		'path' => plugin_dir_path( __FILE__ ),

	);

}

require_once 'app/class-edupack.php';

/**
 * Include all subdirs and files in app
 */
$rii = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( __DIR__ . '/app' ) );
foreach ( $rii as $file ) {
	if ( $file->isDir() ) {
		continue;
	}
	require_once $file->getPathname();
}


/**
 * Include all subdirs and files in lib
 */
$rii = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( __DIR__ . '/lib' ) );
foreach ( $rii as $file ) {
	if ( $file->isDir() ) {
		continue;
	}
	require_once $file->getPathname();
}

edupack_Core_Filters::add_filters();

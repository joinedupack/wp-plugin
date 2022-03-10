<?php

class TemplateFunctions {

	// keys for rows in wp_blogmeta, where we store template status
	protected static $template_meta_key = 'edupack_is_template';
	protected static $discoverable_meta_key = 'edupack_is_discoverable';

	/**
	 * Get all sites marked as templates.
	 *
	 * @return WP_Site[] Matching sites, if any
	 */
	public static function get_templates() {
		$site_query = new WP_Site_Query(
			array(
				'meta_key' => self::$template_meta_key,
				'meta_value' => true,
			)
		);
		return $site_query->get_sites();
	}

	/**
	 * Get all sites marked as discoverable
	 *
	 * @return WP_Site[] Matching sites, if any
	 */
	public static function get_discoverable_templates() {
		$site_query = new WP_Site_Query(
			array(
				'meta_key' => self::$discoverable_meta_key,
				'meta_value' => true,
			)
		);
		return $site_query->get_sites();
	}

	/**
	 * Set is_template meta value to true for the site with the given ID
	 *
	 * @param int $blog_id
	 * @param bool $state
	 * @return int|bool Meta ID if the key didn't exist, true on successful update, false on failure.
	 */
	public static function set_template_status( $blog_id, $state ) {
		return update_site_meta( $blog_id, self::$template_meta_key, $state );
	}


	/**
	 * Set is_discoverable meta value to true for the site with the given ID.
	 *
	 * @param int $blog_id
	 * @param bool $state
	 * @return int|bool Meta ID if the key didn't exist, true on successful update, false on failure.
	 */
	public static function set_discoverability( $blog_id, $state ) {
		return update_site_meta( $blog_id, self::$discoverable_meta_key, $state );
	}


	/**
	 * Sets template/discoverable status of multiple sites at once.
	 *
	 * @param Object[] $templates
	 * @return bool Success/fail.
	 */
	public static function bulk_update_templates( $templates ) {
		// error_log( 'test' );
		if ( empty( $templates ) ) return false; // good enough for now

		foreach ( $templates as $template ) {
			if ( !$template['site_id'] ) continue; // no blog has an ID of 0
			self::set_template_status( $template['site_id'], $template['site_is_template'] );
		}
		return true;
	}

}

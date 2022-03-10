<?php 

class SiteTemplater {

/**
 * Build a new site from the given configuration and return its ID and URL if successful.
 * 
 * Config object looks like this:
 *  int    new_site_id      ID of the newly created site
 *  int    template_site_id   ID of the template site
 *  int    user_id        ID of the user for whom the new site was created
 *  String   site_tagline     Site tagline (Edupack field)
 *  String   site_attribution   Site attribution (Edupack field)
 *  Array[String]  site_keywords  Site Keywords (Edupack field)
 * 
 * 
 * @global Object $wpdb
 * @param  Array  $config
 */
public static function build_from_template( $config ){

	// Declare this very useful global
	global $wpdb;

	// Unpack config
	$new_site_id = $config['new_site_id'];
	$template_site_id = $config['template_site_id'];
	$user_id = $config['user_id'];
	$site_tagline = $config['site_tagline'];
	$site_attribution = $config['site_attribution'];
	$site_keywords = $config['site_keywords'];

	// Switch to the new site
	switch_to_blog( $new_site_id );

	// Begin the transaction
	$wpdb->query("BEGIN;");

	// Clear the area
	static::clear_table( $wpdb->posts );
	static::clear_table( $wpdb->postmeta );
	static::clear_table( $wpdb->comments );
	static::clear_table( $wpdb->commentmeta );

	// Copy stuff over from the template
	static::copy_settings( $template_site_id, $new_site_id );
	static::copy_posts( $template_site_id );
	static::copy_pages( $template_site_id );
	static::copy_comments( $template_site_id );
	static::copy_terms( $template_site_id, $new_site_id );
	// static::copy_users( $template_site_id, $new_site_id, $user_id );
	static::copy_menus( $template_site_id, $new_site_id );
	static::copy_files( $template_site_id, $new_site_id );
	static::copy_additional_tables( $template_site_id, $new_site_id );
	
	// Update more values
	static::set_admin( $template_site_id, $new_site_id, $user_id );
	static::set_content_urls( $template_site_id, $new_site_id );
	static::update_posts_dates( 'post' );
	static::update_posts_dates( 'page' );

	// Ensure the new site is public
	update_blog_status( $new_site_id, 'public', get_blog_status( $template_site_id, 'public' ) );

	// If we get here, everything's fine. Commit the transaction
	$wpdb->query("COMMIT;");

	// Update theme mods after the big commit
	switch_to_blog( $template_site_id );
	$theme_mods = get_theme_mods();
	restore_current_blog();
	if ( is_array( $theme_mods ) ) {
		foreach ( $theme_mods as $theme_mod => $value ) {
		set_theme_mod( $theme_mod, $value );    
		}
	}

	// Edupack fields - attribution needs to come after theme mod updates
	static::add_site_tagline( $site_tagline );
	static::add_site_attribution( $site_attribution );
	static::add_site_keywords( $site_keywords );
	// static::add_site_logo( $site_logo );

	// Back to the calling blog's context
	restore_current_blog();

}

private static function update_posts_dates( $post_type ) {
	global $wpdb;

	$sql = $wpdb->prepare( "UPDATE $wpdb->posts
		SET post_date = %s,
		post_date_gmt = %s,
		post_modified = %s,
		post_modified_gmt = %s
		WHERE post_type = %s
		AND post_status = 'publish'",
		current_time( 'mysql', false ),
		current_time( 'mysql', true ),
		current_time( 'mysql', false ),
		current_time( 'mysql', true ),
		$post_type
	);

	$wpdb->query( $sql );
}

private static function set_content_urls( $template_site_id, $new_site_id ) {
	global $wpdb;

	$pattern = '/^(http|https):\/\//';
	switch_to_blog( $template_site_id );
	$templated_home_url = preg_replace( $pattern, '', home_url() );
	restore_current_blog();

	switch_to_blog( $new_site_id );
	$new_home_url = preg_replace( $pattern, '', home_url() );

	// UPDATE POSTS
	$post_sql = $wpdb->prepare( "SELECT * FROM $wpdb->posts WHERE post_content LIKE %s AND post_status = 'publish';", '%' . $templated_home_url . '%' );
	$post_results = $wpdb->get_results( $post_sql );
	foreach ( $post_results as $post_row ) {
		$post_content = str_replace( $templated_home_url, $new_home_url, $post_row->post_content );
		$post_sql = $wpdb->prepare( "UPDATE $wpdb->posts SET post_content = %s WHERE ID = %d;", $post_content, $post_row->ID );
		$wpdb->query( $post_sql );
	}

	// UPDATE TERM META - required for ACF nav menu options
	$term_sql = $wpdb->prepare( "SELECT * FROM $wpdb->termmeta WHERE meta_value LIKE %s;", '%' . $templated_home_url . '%' );
	$term_results = $wpdb->get_results( $term_sql );
	foreach ( $term_results as $term_row ) {
		$meta_value = str_replace( $templated_home_url, $new_home_url, $term_row->meta_value );
		$term_sql = $wpdb->prepare( "UPDATE $wpdb->termmeta SET meta_value = %s WHERE meta_id = %d;", $meta_value, $term_row->meta_id );
		$wpdb->query( $term_sql );
	}

	restore_current_blog();
}

private static function copy_settings( $template_site_id, $new_site_id ) {
	global $wpdb;

	$exclude_public_option = '';
	if ( class_exists( 'ProSites' ) ) {
		$exclude_public_option = "AND `option_name` != 'blog_public'";
	}

	$exclude_settings = "`option_name` != 'siteurl' AND `option_name` != 'blogname' AND `option_name` != 'admin_email' AND `option_name` != 'new_admin_email' AND `option_name` != 'home' AND `option_name` != 'upload_path' AND `option_name` != 'db_version' AND `option_name` != 'secret' AND `option_name` != 'fileupload_url' AND `option_name` != 'nonce_salt' {$exclude_public_option}";
	$new_prefix = $wpdb->get_blog_prefix($new_site_id);

	//Delete the current options, except blog-specific options
	$wpdb->query("DELETE FROM $wpdb->options WHERE $exclude_settings");

	if ( ! $wpdb->last_error ) {
		//No error. Good! Now copy over the old settings

		//Switch to the template blog, then grab the settings/plugins/templates values from the template blog
		switch_to_blog( $template_site_id );

		$src_blog_settings = $wpdb->get_results("SELECT * FROM $wpdb->options WHERE $exclude_settings");
		$template_prefix = $wpdb->get_blog_prefix($template_site_id);

		//Switch back to the newly created blog
		restore_current_blog();

		//Now, insert the templated settings into the newly created blog
		foreach ( $src_blog_settings as $row ) {
			//Make sure none of the options are using wp_X_ convention, and if they are, replace the value with the new blog ID
			$row->option_name = str_replace( $template_prefix, $new_prefix, $row->option_name );
			if ( 'sidebars_widgets' != $row->option_name ) /* <-- Added this to prevent unserialize() call choking on badly formatted widgets pickled array */
				$row->option_value = str_replace( $template_prefix, $new_prefix, $row->option_value );

			//To prevent duplicate entry errors, since we're not deleting ALL of the options, there could be an ID collision
			unset( $row->option_id );

			if ( ! $row )
				continue; // Prevent empty row insertion

			//Insert the row
			$wpdb->insert( $wpdb->options, (array)$row );

			//Check for errors
			if ( ! empty( $wpdb->last_error ) ) {
				$error = '<div id="message" class="error"><p>' . sprintf( __( 'Insertion Error: %s - The template was not applied. (Edupack - While inserting templated settings)', 'edupack-templater' ), $wpdb->last_error ) . '</p></div>';
				$wpdb->query("ROLLBACK;");

				//We've rolled it back and thrown an error, we're done here
				restore_current_blog();
				wp_die( $error );
			}
		}

		$source_blog_details = get_blog_details( $template_site_id );
		$new_blog_details = array(
			'public' => $source_blog_details->public,
			'archived' => $source_blog_details->archived,
			'mature' => $source_blog_details->mature,
			'spam' => $source_blog_details->spam,
			'deleted' => $source_blog_details->deleted,
			'lang_id' => $source_blog_details->lang_id
		);

		update_blog_details( $new_site_id, $new_blog_details );
	}
	else {
		$error = '<div id="message" class="error"><p>' . sprintf( __( 'Deletion Error: %s - The template was not applied. (Edupack - While removing auto-generated settings)', 'edupack-templater' ), $wpdb->last_error ) . '</p></div>';
		$wpdb->query("ROLLBACK;");
		restore_current_blog(); //Switch back to our current blog
		wp_die($error);
	}
}

private static function copy_posts( $template_site_id ) {
	static::copy_posts_table($template_site_id, 'posts' );
	static::copy_posts_table($template_site_id, 'postmeta' );
}

private static function copy_pages( $template_site_id ) {
	static::copy_posts_table($template_site_id, "pages" );
	static::copy_posts_table($template_site_id, "pagemeta" );
}

private static function copy_comments( $template_site_id ) {
	global $wpdb;

	switch_to_blog( $template_site_id );
	$source_comments = $wpdb->get_results( "SELECT * FROM $wpdb->comments" );
	$source_commentmeta = $wpdb->get_results( "SELECT * FROM $wpdb->commentmeta" );
	restore_current_blog();

	foreach ( $source_comments as $comment ) {
		$_comment = (array)$comment;
		$wpdb->insert(
			$wpdb->comments,
			$_comment
		);
	}
}

private static function copy_terms( $template_site_id, $new_site_id ) {
	global $wpdb;

	static::clear_table( $wpdb->links );
	static::copy_table($template_site_id, $new_site_id, $wpdb->links );

	static::clear_table( $wpdb->terms );
	static::copy_table($template_site_id, $new_site_id, $wpdb->terms );

	static::clear_table( $wpdb->term_relationships );
	static::copy_table($template_site_id, $new_site_id, $wpdb->term_relationships );

	static::clear_table( $wpdb->term_taxonomy );
	static::copy_table($template_site_id, $new_site_id, $wpdb->term_taxonomy );

	if ( isset( $wpdb->termmeta ) ) {
		static::clear_table( $wpdb->termmeta );
		static::copy_table($template_site_id, $new_site_id, $wpdb->termmeta );
	}

	// Delete those terms related to menus
	switch_to_blog( $new_site_id );
	$wpdb->query( "DELETE FROM $wpdb->terms WHERE term_id IN (SELECT term_id FROM $wpdb->term_taxonomy WHERE taxonomy = 'nav_menu')" );
	$wpdb->query( "DELETE FROM $wpdb->term_relationships WHERE term_taxonomy_id IN (SELECT term_taxonomy_id FROM $wpdb->term_taxonomy WHERE taxonomy = 'nav_menu')" );
	$wpdb->query( "DELETE FROM $wpdb->term_taxonomy WHERE taxonomy = 'nav_menu'" );
	restore_current_blog();
}

private static function copy_users( $template_site_id, $new_site_id, $user_id ) {
	global $wpdb;

	switch_to_blog( $template_site_id );
	$template_users = get_users();
	restore_current_blog();

	if ( ! empty( $template_users ) ) {
		foreach( $template_users as $user ) {
			if ( $user->ID == $user_id ) {
				add_user_to_blog( $new_site_id, $user->ID, 'administrator' );
			}
			else {
				add_user_to_blog( $new_site_id, $user->ID, $user->roles[0] );
			}
		}
	}
}

private static function set_admin( $template_site_id, $new_site_id, $user_id ) {
	add_user_to_blog( $new_site_id, $user_id, 'administrator' );
}

private static function copy_menus( $template_site_id, $new_site_id ) {
	static::copy_menu($template_site_id, $new_site_id );
	static::set_menus_urls($template_site_id, $new_site_id );
}

private static function copy_files( $template_site_id, $new_site_id ) {
	global $wp_filesystem, $wpdb;

	// We need to copy the attachment post type from posts table
	static::copy_posts_table($template_site_id, 'attachment' );
	static::copy_posts_table($template_site_id, 'attachmentmeta' );

	$new_content_url = get_bloginfo('wpurl');

	switch_to_blog($template_site_id );
	$theme_slug = get_option( 'stylesheet' );

	// Attachments URL for the template blogç
	$template_attachments = get_posts( array( 'post_type' => 'attachment' ) );
	$template_content_url = get_bloginfo('wpurl');
	//Now, go back to the new blog that was just created
	restore_current_blog();

	$dir_to_copy = static::_get_files_fs_path($template_site_id ); //ABSPATH . 'wp-content/blogs.dir/' .$template_site_id . '/files';
	$dir_to_copy_into = static::_get_files_fs_path( $new_site_id ); //ABSPATH .'wp-content/blogs.dir/' . $blog_id . '/files';

	if ( is_dir( $dir_to_copy ) ) {
		$result = wp_mkdir_p( $dir_to_copy_into );
		if ($result) {

			include_once( ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php' );
			include_once( ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php' );

			if ( is_object( $wp_filesystem ) )
				$orig_filesystem = wp_clone( $wp_filesystem );
			else
				$orig_filesystem = $wp_filesystem;

			$wp_filesystem = new WP_Filesystem_Direct( false );

			if ( ! defined('FS_CHMOD_DIR') )
				define('FS_CHMOD_DIR', 0755 );
			if ( ! defined('FS_CHMOD_FILE') )
				define('FS_CHMOD_FILE', 0644 );

			$result = copy_dir( $dir_to_copy, $dir_to_copy_into );

			unset( $wp_filesystem );

			if ( is_object( $orig_filesystem ) )
				$wp_filesystem = wp_clone( $orig_filesystem );
			else
				$wp_filesystem = $orig_filesystem;

			if ( @file_exists( $dir_to_copy_into . '/sitemap.xml' ) )
				@unlink( $dir_to_copy_into . '/sitemap.xml' );

			// If we set the same theme, we need to replace URLs in theme mods
			$mods = is_array( get_theme_mods() ) ? get_theme_mods() : array();
			array_walk_recursive( $mods, 'SiteTemplater::set_theme_mods_url', array( $template_content_url, $new_content_url,$template_site_id, $new_site_id ) );
			update_option( "theme_mods_$theme_slug", $mods );

			// We need now to change the attachments URLs
			$attachment_guids = array();
			foreach ( $template_attachments as $attachment ) {
				$new_url = str_replace( $template_content_url, $new_content_url, dirname( $attachment->guid ) );
				$new_url = str_replace( 'sites/' .$template_site_id, 'sites/' . $new_site_id, $new_url );
				$new_url = str_replace( 'blogs.dir/' .$template_site_id, 'blogs.dir/' . $new_site_id, $new_url );

				// We get an array with key = old_url and value = new_url
				$attachment_guids[ dirname( $attachment->guid ) ] = $new_url;
			}

			static::set_attachments_urls( $attachment_guids );


		} else {
			$error = '<div id="message" class="error"><p>' . sprintf( __( 'File System Error: Unable to create directory %s. (Edupack - While copying files)', 'edupack-templater' ), $dir_to_copy_into ) . '</p></div>';
			$wpdb->query( 'ROLLBACK;' );
			restore_current_blog();
			wp_die( $error );

		}
	}
}

private static function set_theme_mods_url( &$item, $key, $userdata = array() ) {
	$template_upload_url = $userdata[0];
	$new_upload_url = $userdata[1];
	$template_blog_id = $userdata[2];
	$new_blog_id = $userdata[3];

	if ( ! $template_upload_url || ! $new_upload_url )
			return;

	if ( is_string( $item ) ) {
			$item = str_replace( $template_upload_url, $new_upload_url, $item );
			$item = str_replace( 'sites/' . $template_blog_id . '/', 'sites/' . $new_blog_id . '/', $item );
			$item = str_replace( 'blogs.dir/' . $template_blog_id . '/', 'blogs.dir/' . $new_blog_id . '/', $item );
	}

}

/**
 * @param int $template_site_id The ID of the blog to copy
 * @param string $type post, page, postmeta or pagemeta
 */
private static function copy_posts_table( $template_site_id, $type, $categories = false ) {
	global $wpdb;

	switch( $type ) {
		case 'posts': $table = 'posts'; break;
		case 'postmeta': $table = 'postmeta'; break;
		case 'pages': $table = 'posts'; break;
		case 'pagemeta': $table = 'postmeta'; break;
		case 'attachment': $table = 'posts'; break;
		case 'attachmentmeta': $table = 'postmeta'; break;
	}

	do_action('blog_templates-copying_table', $table, $template_site_id);

	//Switch to the template blog, then grab the values
	switch_to_blog($template_site_id);
	$query = "SELECT t1.* FROM {$wpdb->$table} t1 ";

	if ( 'posts' == $type ) {
		if ( is_array( $categories ) && count( $categories ) > 0 )
			$query .= " INNER JOIN $wpdb->term_relationships t2 ON t2.object_id = t1.ID ";

		$query .= "WHERE t1.post_type != 'page' && t1.post_type != 'attachment' && t1.post_type != 'nav_menu_item'";

		if ( is_array( $categories ) && count( $categories ) > 0 ) {
			$categories_list = '(' . implode( ',', $categories ) . ')';
			$query .= " AND t2.term_taxonomy_id IN $categories_list GROUP BY t1.ID";
		}

	}
	elseif ( 'postmeta' == $type ) {
		$query .= "INNER JOIN $wpdb->posts t2 ON t1.post_id = t2.ID WHERE t2.post_type != 'page' && t2.post_type != 'attachment' && t2.post_type != 'nav_menu_item'";
	}
	elseif ( 'pages' == $type ) {
		$query .= "WHERE t1.post_type = 'page'";

		$pages_ids = $categories;
		if ( is_array( $pages_ids ) && count( $pages_ids ) > 0 ) {
			$query .= " AND t1.ID IN (" . implode( ',', $pages_ids ) . ")";
		}
	}
	elseif ( 'pagemeta' == $type ) {
		$query .= "INNER JOIN $wpdb->posts t2 ON t1.post_id = t2.ID WHERE t2.post_type = 'page'";

		$pages_ids = $categories;
		if ( is_array( $pages_ids ) && count( $pages_ids ) > 0 ) {
			$query .= " AND t2.ID IN (" . implode( ',', $pages_ids ) . ")";
		}
	}
	elseif ( 'attachment' == $type ) {
		$query .= "WHERE t1.post_type = 'attachment'";
	}
	elseif ( 'attachmentmeta' == $type ) {
		$query .= "INNER JOIN $wpdb->posts t2 ON t1.post_id = t2.ID WHERE t2.post_type = 'attachment'";
	}

	$templated = $wpdb->get_results( $query );
	restore_current_blog(); //Switch back to the newly created blog

	if ( count( $templated ) )
		$to_remove = static::get_fields_to_remove( $wpdb->$table, $templated[0] );

	//Now, insert the templated settings into the newly created blog
	foreach ( $templated as $row ) {
		$row = (array)$row;

		foreach ( $row as $key => $value ) {
			if ( in_array( $key, $to_remove ) )
				unset( $row[$key] );
		}

		$process = apply_filters( 'blog_templates-process_row', $row, $table, $template_site_id );
		if ( ! $process )
			continue;

		$wpdb->insert( $wpdb->$table, $process );
		if ( ! empty( $wpdb->last_error ) ) {
			$error = '<div id="message" class="error"><p>' . sprintf( __( 'Insertion Error: %1$s - The template was not applied. (Edupack - While copying %2$s)', 'edupack-templater' ), $wpdb->last_error, $table ) . '</p></div>';
			$wpdb->query("ROLLBACK;");

			//We've rolled it back and thrown an error, we're done here
			restore_current_blog();
			wp_die($error);
		}
	}
}


private static function clear_table( $table ) {
	global $wpdb;

	//Delete the current categories
	$wpdb->query("DELETE FROM $table");

	if ($wpdb->last_error) { //No error. Good! Now copy over the terms from the templated blog
		$error = '<div id="message" class="error"><p>' . sprintf( __( 'Deletion Error: %1$s - The template was not applied. (Edupack - While clearing %2$s)', 'edupack-templater' ), $wpdb->last_error, $table ) . '</p></div>';
		$wpdb->query("ROLLBACK;");
		restore_current_blog(); //Switch back to our current blog
		wp_die($error);
	}
}

private static function copy_table( $template_site_id, $new_site_id, $dest_table ) {
	global $wpdb;

	$destination_prefix = $wpdb->get_blog_prefix($new_site_id);

	//Switch to the template blog, then grab the values
	switch_to_blog( $template_site_id );
	$template_prefix = $wpdb->get_blog_prefix($template_site_id);
	$source_table = str_replace( $destination_prefix, $template_prefix, $dest_table );
	$templated = $wpdb->get_results( "SELECT * FROM {$source_table}" );
	restore_current_blog(); //Switch back to the newly created blog

	if ( count( $templated ) )
		$to_remove = static::get_fields_to_remove($dest_table, $templated[0]);

	//Now, insert the templated settings into the newly created blog
	foreach ($templated as $row) {
		$row = (array)$row;

		foreach ( $row as $key => $value ) {
			if ( in_array( $key, $to_remove ) )
			unset( $row[ $key ] );
		}

		$process = apply_filters('blog_templates-process_row', $row, $dest_table, $template_site_id);
		if ( ! $process )
		continue;

		$wpdb->insert( $dest_table, $process );
		if ( ! empty( $wpdb->last_error ) ) {
			$error = '<div id="message" class="error"><p>' . sprintf( __( 'Insertion Error: %1$s - The template was not applied. (Edupack - While copying %2$s)', 'edupack-templater' ), $wpdb->last_error, $dest_table ) . '</p></div>';
			$wpdb->query("ROLLBACK;");

			//We've rolled it back and thrown an error, we're done here
			restore_current_blog();
			wp_die($error);
		}
	}
}

private static function copy_menu( $template_site_id, $new_site_id ) {
	global $wpdb;

	switch_to_blog( $template_site_id );
	$templated_posts_table = $wpdb->posts;
	$templated_postmeta_table = $wpdb->postmeta;
	$templated_terms_table = $wpdb->terms;
	$templated_term_taxonomy_table = $wpdb->term_taxonomy;
	$templated_term_relationships_table = $wpdb->term_relationships;

	$menu_locations = get_nav_menu_locations();
	restore_current_blog();

	switch_to_blog( $new_site_id );
	$new_posts_table = $wpdb->posts;
	$new_postmeta_table = $wpdb->postmeta;
	$new_terms_table = $wpdb->terms;
	$new_term_taxonomy_table = $wpdb->term_taxonomy;
	$new_term_relationships_table = $wpdb->term_relationships;

	$new_blog_locations = $menu_locations;

	set_theme_mod( 'nav_menu_locations', $new_blog_locations );
	restore_current_blog();

	// First, the menus
	$menus = $wpdb->get_results( "SELECT * FROM $templated_terms_table t 
		JOIN $templated_term_taxonomy_table tt ON t.term_id = tt.term_id 
		WHERE taxonomy = 'nav_menu'" 
	);

	if ( ! empty( $menus ) ) {
		foreach ( $menus as $menu ) {

			// Inserting the menu
			$wpdb->query(
				$wpdb->prepare(
					"INSERT IGNORE INTO $new_terms_table
					(term_id, name, slug, term_group)
					VALUES
					(%d, %s, %s, %d)",
					$menu->term_id,
					$menu->name,
					$menu->slug,
					$menu->term_group
				)
			);

		// Terms taxonomies
		$term_taxonomies = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM $templated_term_taxonomy_table
					WHERE term_id = %d",
					$menu->term_id
				)
			);

			$terms_taxonomies_ids = array();
			foreach ( $term_taxonomies as $term_taxonomy ) {
				$terms_taxonomies_ids[] = $term_taxonomy->term_taxonomy_id;

				// Inserting terms taxonomies
				$wpdb->query(
					$wpdb->prepare(
						"INSERT IGNORE INTO $new_term_taxonomy_table
						(term_taxonomy_id, term_id, taxonomy, description, parent, count)
						VALUES
						(%d, %d, %s, %s, %d, %d)",
						$term_taxonomy->term_taxonomy_id,
						$term_taxonomy->term_id,
						$term_taxonomy->taxonomy,
						empty( $term_taxonomy->description ) ? '' : $term_taxonomy->description,
						$term_taxonomy->parent,
						$term_taxonomy->count
					)
				);
			}

			$terms_taxonomies_ids = implode( ',', $terms_taxonomies_ids );

			$term_relationships = $wpdb->get_results(
					"SELECT * FROM $templated_term_relationships_table
					WHERE term_taxonomy_id IN ( $terms_taxonomies_ids )"
			);

			$objects_ids = array();
			foreach ( $term_relationships as $term_relationship ) {
				$objects_ids[] = $term_relationship->object_id;

				// Inserting terms relationships
				$wpdb->query(
					$wpdb->prepare(
						"INSERT IGNORE INTO $new_term_relationships_table
						(object_id, term_taxonomy_id, term_order)
						VALUES
						(%d, %d, %d)",
						$term_relationship->object_id,
						$term_relationship->term_taxonomy_id,
						$term_relationship->term_order
					)
				);
			}

			// We need to split the queries here due to MultiDB issues

			// Inserting the objects
			$objects_ids = implode( ',', $objects_ids );

			$objects = $wpdb->get_results( "SELECT * FROM $templated_posts_table
				WHERE ID IN ( $objects_ids )", ARRAY_N );

			foreach ( $objects as $object ) {
				$values = '("' . implode( '","', esc_sql( $object ) ) . '")';
				$wpdb->query( "INSERT IGNORE INTO $new_posts_table VALUES $values" );
			}


			// Inserting the objects meta
			$objects_meta = $wpdb->get_results( "SELECT * FROM $templated_postmeta_table
				WHERE post_id IN ( $objects_ids )", ARRAY_N );

			foreach ( $objects_meta as $object_meta ) {
				$values = '("' . implode( '","', esc_sql( $object_meta ) ) . '")';
				$wpdb->query( "INSERT IGNORE INTO $new_postmeta_table VALUES $values" );
			}

		}

	}
}

/**
 * Added to automate comparing the two tables, and making sure no old 
 * fields that have been removed get copied to the new table
 */
private static function get_fields_to_remove( $new_table_name, $old_table_row ) {
	//make sure we have something to compare it to
	if ( empty( $old_table_row ) )
		return false;

	//We need the old table row to be in array format, so we can use in_array()
	$old_table_row = (array)$old_table_row;

	global $wpdb;

	//Get the new table structure
	$new_table = (array)$wpdb->get_results( "SHOW COLUMNS FROM {$new_table_name}" );

	$new_fields = array();
	foreach( $new_table as $row ) {
		$new_fields[] = $row->Field;
	}

	$results = array();

	//Now, go through the columns in the old table, and check if there are any that don't show up in the new table
	foreach ( $old_table_row as $key => $value ) {
		if ( ! in_array( $key,$new_fields ) ) { //If the new table doesn't have this field
			//There's a column that isn't in the new one, make note of that
			$results[] = $key;
		}
	}

	//Return the results array, which should contain all of the fields that don't appear in the new table
	return $results;
}

private static function set_menus_urls( $template_site_id, $new_site_id ) {
	global $wpdb;

	$pattern = '/^(http|https):\/\//';
	switch_to_blog( $template_site_id );
	$templated_home_url = preg_replace( $pattern, '', home_url() );
	restore_current_blog();

	switch_to_blog( $new_site_id );
	$new_home_url = preg_replace( $pattern, '', home_url() );

	$sql = "SELECT * FROM $wpdb->postmeta WHERE meta_key = '_menu_item_url';";
	$results = $wpdb->get_results( $sql );

	foreach ( $results as $row ) {
			$meta_value = preg_replace( $pattern, '', $row->meta_value );
			if ( strpos( $meta_value, $templated_home_url ) !== false ) {
					//UPDATE
					$meta_value = str_replace( $templated_home_url, $new_home_url, $row->meta_value );
					$sql = $wpdb->prepare( "UPDATE $wpdb->postmeta SET meta_value = %s WHERE meta_id = %d;", $meta_value, $row->meta_id );
					$wpdb->query( $sql );
			}
	}
	restore_current_blog();
}

private static function set_attachments_urls( $attachment_guids ) {
	global $wpdb;

	$queries = array();
	foreach ( $attachment_guids as $old_guid => $new_guid ) {
		$queries[] = $wpdb->prepare( "UPDATE $wpdb->posts SET guid = REPLACE( guid, '%s', '%s' ) WHERE post_type = 'attachment'",
			$old_guid,
			$new_guid
		);
	}

	foreach ( $queries as $query )
		$wpdb->query( $query );

}

/**
 * Proper blog filesystem path finding.
 */
private static function _get_files_fs_path( $blog_id ) {
	if ( ! is_numeric( $blog_id ) )
			return false;

	switch_to_blog( $blog_id );
	$info = wp_upload_dir();
	restore_current_blog();

	return ! empty( $info['basedir'] ) ? $info['basedir'] : false;
}

private static function copy_additional_tables( $template_site_id, $new_site_id ) {
	global $wpdb;

	// See NOTE below
	// $tables_to_copy = $this->settings['additional_tables'];
	
	// If we have copied the settings, we'll need at least to create all the tables (we always copy settings)
	$all_source_tables = wp_list_pluck( static::get_additional_tables( $template_site_id ), 'prefix.name' );
	
	$table_pairs = static::associate_tables( $all_source_tables, $template_site_id, $new_site_id );
	
	foreach ( $table_pairs as $source_table => $new_table ) {
		// NOTE: This part may need to be re-evaluated. Currently we are copying all additional tables
		// $add = in_array( $new_table, $tables_to_copy );
		$add = true;

		$result = $wpdb->get_results( "SHOW TABLES LIKE '{$new_table}'", ARRAY_N );
		if ( ! empty( $result ) ) {
			// The table is already present in the new blog
			// Clear it
			static::clear_table( $new_table );

			if ( $add ) {
				// And copy the content if needed
				static::copy_table( $template_site_id, $new_site_id, $new_table );
			}
		}
		else {
			// The table does not exist in the new blog
			// Let's create it
			$create_script = current( $wpdb->get_col( 'SHOW CREATE TABLE ' . $source_table, 1 ) );

			if ( $create_script && preg_match( '/\(.*\)/s', $create_script, $match ) ) {
				$table_body = $match[0];
				$table_body = str_replace(
					array_keys( $table_pairs ),
					array_values( $table_pairs ),
					$table_body
				);

				$create_table_query = "CREATE TABLE IF NOT EXISTS {$new_table} {$table_body}";

				$wpdb->query( 'SET foreign_key_checks = 0' );
				$wpdb->query( $create_table_query );
				$wpdb->query( 'SET foreign_key_checks = 1' );

				if ( $add ) {
					// And copy the content if needed
					if ( is_a( $wpdb, 'm_wpdb' ) ) {
						$rows = $wpdb->get_results( "SELECT * FROM {$source_table}", ARRAY_A );
						foreach ( $rows as $row ) {
							$wpdb->insert( $new_table, $row );
						}
					} else {
						$wpdb->query( "INSERT INTO {$new_table} SELECT * FROM {$source_table}" );
					}
				}

			}

			if ( ! empty( $wpdb->last_error ) ) {
				$error = '<div id="message" class="error"><p>' . sprintf( __( 'Insertion Error: %s - The template was not applied. (Edupack - With CREATE TABLE query for Additional Tables)', 'edupack-templater' ), $wpdb->last_error ) . '</p></div>';
				$wpdb->query("ROLLBACK;");
				wp_die($error);
			}
		}

	}
}

/**
 * Gets non-default tables from a site
 */
private static function get_additional_tables( $blog_id ) {
	global $wpdb;

	$blog_id = absint( $blog_id );
	$blog_details = get_blog_details( $blog_id );

	if ( ! $blog_details )
		return array();


	switch_to_blog( $blog_id );
	
	// MultiDB Plugin hack
		$pfx = class_exists( "m_wpdb" ) ? $wpdb->get_blog_prefix($blog_id) : str_replace( '_', '\_', $wpdb->get_blog_prefix($blog_id) );

		// Get all the tables for that blog
		$results = $wpdb->get_results("SHOW TABLES LIKE '{$pfx}%'", ARRAY_N);

		$default_tables = array( 'posts', 'comments', 'links', 'options', 'postmeta', 'terms', 'term_taxonomy', 'termmeta', 'term_relationships', 'commentmeta' );

		$tables = array();
		if ( ! empty( $results ) ) {
			foreach ( $results as $result ) {
				if ( ! in_array( str_replace( $wpdb->get_blog_prefix($blog_id), '', $result['0'] ), $default_tables ) ) {
					if ( class_exists( 'm_wpdb' ) ) {
						// MultiDB Plugin
						$db = $wpdb->analyze_query( "SHOW TABLES LIKE '{$pfx}%'" );
						$dataset = $db['dataset'];
						$current_db = '';

						foreach ( $wpdb->dbh_connections as $connection ) {
							if ( $connection['ds'] == $dataset ) {
								$current_db = $connection['name'];
								break;
							}
						}

						$val = $current_db . '.' . $result[0];

					} else {
						$val =  $result[0];
					}

					if ( stripslashes_deep( $pfx ) == $wpdb->base_prefix ) {
						// If we are on the main blog, we'll have to avoid those tables from other blogs
						$pattern = '/^' . stripslashes_deep( $pfx ) . '[0-9]/';
						if ( preg_match( $pattern, $result[0] ) )
							continue;
					}

					$tables[] = array( 
						'name' => $result[0] ,
						'prefix.name' => $val
					);
				}
			}
		}

		restore_current_blog();

		return $tables;
		// End changed
}

/**
 * Generates an array associating source table names with target table names with new prefix
 */
private static function associate_tables( $tables = array(), $template_site_id, $new_site_id ) {

	if ( empty( $tables ) ) {
			return array();
	}

	global $wpdb;
	$paired_tables     = array();
	$new_prefix        = $wpdb->get_blog_prefix( $new_site_id );
	$template_prefix   = $wpdb->get_blog_prefix( $template_site_id );

	foreach ( $tables as $source_table ) {
		$target_table = esc_sql( $source_table );

		// MultiDB Hack
		if ( is_a( $wpdb, 'm_wpdb' ) ) {
			$tablebase = end( explode( '.', $target_table, 2 ) );
		} else {
			$tablebase = $target_table;
		}

		$new_table = $new_prefix . substr( $tablebase, strlen( $template_prefix ) );

		$paired_tables[ $source_table ] = $new_table;
	}

	return $paired_tables;

	}

/**
 * ************************************************
 *  ========== EDUPACK-SPECIFIC FUNCTIONS ==========
 * ************************************************
 */

 /**
	* Tagline is just another name for the blog description
	*/
private static function add_site_tagline( $site_tagline ) {
	$site_tagline = strip_tags($site_tagline);
	$site_tagline = str_replace("\\\"", "\"", $site_tagline);
	$site_tagline = str_replace("\'", "'", $site_tagline);
	update_option('blogdescription', $site_tagline);
}

/**
 * Attribution is stored as a theme mod
 */
private static function add_site_attribution( $site_attribution ) {
	set_theme_mod( 'theme_edupack_attribution', $site_attribution );
}

/**
 * Keywords are serialized as CSVs in site meta under the key 'site-tags'
 */
private static function add_site_keywords( $site_keywords ) {
	if( is_array( $site_keywords ) ) {
		add_site_meta( get_current_blog_id(), 'site-tags', implode(',', $site_keywords) );
	}
}

private static function add_site_logo( $site_logo ) {
	// TODO
} 

}
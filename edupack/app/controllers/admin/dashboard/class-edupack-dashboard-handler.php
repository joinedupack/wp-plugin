<?php
/**
 * Edupack Dashboard Handler
 *
 * @package edupack
 * @version 1.0.0
 */

/**
 * Backend functionality used in the Dashboard.
 */
class Edupack_Dashboard_Handler extends Edupack {

	/**
	 * The class constructor
	 */
	public function __construct() {
		$this->add_actions();
		$this->cron_jobs();
	}

	/**
	 * WP actions.
	 */
	public function add_actions() {

		add_action( 'rest_api_init', array( $this, 'register_rest_endpoint' ) );
		add_action( 'edupack_monthly_status_stats_cron', array( $this, 'edupack_monthly_status_stats_cron' ) );

		if ( isset( $_GET['page'] ) && 'dashboard' === $_GET['page'] && isset( $_GET['tab'] ) && 'reports' === $_GET['tab'] || ! isset( $_GET['tab'] ) ) {
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
			add_action( 'admin_init', array( $this, 'download_dashboard_stats' ) );
		}
	}

	/**
	 * Register the dashboard cron jobs.
	 */
	public function cron_jobs() {

		// Don't register the cron jobs if it is not the root site.
		if ( get_main_network_id() !== get_current_blog_id() ) {
			return;
		}

		// Schedule the cron for warning emails
		if ( ! wp_next_scheduled( 'edupack_get_site_statuses' ) ) {
			wp_schedule_event( strtotime( 'midnight' ), 'daily', 'edupack_get_site_statuses' );
		}
		add_action( 'edupack_get_site_statuses', array( $this, 'maybe_run_monthly_status_cron' ) );
	}

	/**
	 * Check if it's the first of the month or not.
	 */
	public function maybe_run_monthly_status_cron() {

		// If this is the last day in the month, get the stats.
		if ( date( 't' ) === date( 'd' ) ) {
			do_action( 'edupack_monthly_status_stats_cron' );
		}
	}

	/**
	 * Get the site statuses for this month.
	 */
	public function edupack_monthly_status_stats_cron() {

		// Variable setup.
		$month = strtolower( date( 'M' ) );
		$date  = date( 'Y-m' );

		// We will append to this array in the loop.
		$global_stats = array(
			'active'   => 0,
			'stale'    => 0,
			'archived' => 0,
			'date'     => $date,
			'month'    => $month,
		);

		// Get all the sites.
		$sites = get_sites(
			array(
				'limit'      => -1,
				'network_id' => get_current_network_id(),
			)
		);

		// Loop over the sites.
		foreach ( $sites as $key => $site ) {

			$status         = '';
			$monthly_status = array();

			if ( '0' === $site->public ) {
				$status = 'archived';
			} else {

				// Is it stale.
				$stale = get_site_meta( $site->blog_id, 'edulab_warning_email_sent', true );

				if ( '1' === $site->public && $stale && 1 === $stale ) {
					$status = 'stale';
				} else {
					$status = 'active';
				}
			}

			// Increment the global stat for this status.
			$global_stats[ $status ] = $global_stats[ $status ] + 1;

			// Switch to the site.
			switch_to_blog( $site->blog_id );

			// Get the site option && append.
			$monthly_status = get_option( 'edupack_monthly_site_status' ) ?? array();

			/**
			 * Check if we have already added this month,
			 * if we have remove, and append latest data.
			 */
			if ( $monthly_status && count( $monthly_status ) > 0 ) {

				$last_item_added = $monthly_status[ array_key_last( $monthly_status ) ];

				if ( $last_item_added ) {

					if ( date( 'Y-m' ) === $last_item_added['date'] ) {

						// Remove if this month already set.
						array_pop( $monthly_status );
					}
				}
			}

			// Append the new data.
			$monthly_status[] = array(
				'status' => $status,
				'date'   => $date,
				'month'  => $month,
			);

			// Update the site status site_option.
			update_option( 'edupack_monthly_site_status', $monthly_status );

			// Return to the network.
			restore_current_blog();
		}

		// Get the global status option.
		$global_status_stats = get_network_option( get_current_network_id(), 'edupack_monthly_global_site_statuses' ) ?? array();

		// Append the new data
		$global_status_stats[] = $global_stats;

		// Update the network option.
		update_network_option( get_current_network_id(), 'edupack_monthly_global_site_statuses', $global_status_stats );
	}

	/**
	 * Enqueue Required Scripts
	 */
	public function enqueue_scripts() {

		wp_enqueue_script( 'chart-js', $this->get_dist_js_url( 'external/chart.min.js' ), array(), '3.4.1', true );
		wp_enqueue_script( 'dashboard-charts', $this->get_dist_js_url( 'dashboard-charts.js' ), array( 'chart-js' ), '1.0.0', true );

		/**
		 *  We need to use these in the js file
		 *  to run fetch requests to the api.
		 */
		wp_localize_script(
			'dashboard-charts',
			'rest',
			array(
				'url'   => get_rest_url(),
				'nonce' => wp_create_nonce( 'wp_rest' ),
			)
		);
	}

	/**
	 * Register rest endpoints.
	 */
	public function register_rest_endpoint() {

		/**
		 * Rest route for stats about the current site statuses.
		 */
		register_rest_route(
			'edupack/stats',
			'/current-site-statuses',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'get_current_site_statuses' ),
				'permission_callback' => function( $request ) {
					return is_user_logged_in() && current_user_can( 'manage_options' );
				},
			)
		);

		/**
		 * Rest route for stats about site status changes over time.
		 */
		register_rest_route(
			'edupack/stats',
			'/status-over-time',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'get_statuses_over_time' ),
				'permission_callback' => function( $request ) {
					return is_user_logged_in() && current_user_can( 'manage_options' );
				},
			)
		);
	}

	/**
	 * Get the current site statuses.
	 */
	public function get_current_site_statuses( $request ) {

		// Initial variable setup.
		$formatted_sites = array();

		$args = array(
			'limit'      => -1,
			'network_id' => get_current_network_id(),
		);

		// Loop the sites here.
		$sites = get_sites(
			$args
		);

		$statuses = array(
			0, // Active
			0, // Archived
			0, // Stale
		);

		foreach ( $sites as $key => $site ) {

			// Reset the formatted site array.
			$formatted_site = array();

			$stale = get_site_meta( $site->blog_id, 'edulab_warning_email_sent', true );

			if ( '0' === $site->public ) {
				$status      = 'archived';
				$statuses[1] = $statuses[1] + 1;
			} elseif ( '1' === $site->public && $stale && 1 === $stale ) {

				$status      = 'stale';
				$statuses[2] = $statuses[2] + 1;

				// If the user clicked download tell us which site is stale.
				if ( isset( $request['edupack_download_stats'] ) ) {
					$formatted_site['is_stale'] = true;
				}
			} else {
				$status      = 'active';
				$statuses[0] = $statuses[0] + 1;
			}

			// If we are downloading, setup a formatted site array.
			if ( isset( $request['edupack_download_stats'] ) ) {

				$blog_details = get_blog_details( $site->blog_id );

				$formatted_sites[] = array(
					// 'domain'       => $site->domain,
					// 'path'         => $site->path,
					'name'         => $blog_details->blogname,
					'site_url'     => get_home_url( $site->blog_id ),
					'status'       => $status,
					'registered'   => $site->registered,
					'last_updated' => $site->last_updated,
					'post_count'   => $blog_details->post_count,
				);
			}
		}

		// If the user clicked download return the sites as well.
		if ( ! isset( $request['edupack_download_stats'] ) ) {
			return $statuses;
		} else {
			return array(
				'sites' => $formatted_sites,
				'stats' => $statuses,
			);
		}
	}

	/**
	 * Get archived & active statuses over time.
	 */
	public function get_statuses_over_time( $request ) {

		// Initial variables.
		$result    = array();
		$increment = 0;

		// Get the global network option.
		$monthly_stats = get_network_option( get_current_network_id(), 'edupack_monthly_global_site_statuses' );

		// Loop the network stats.
		foreach ( $monthly_stats as $key => $monthly_stat ) {

			// Skip stat if it's the current month.
			if ( strtolower( date( 'Y-m' ) ) === $monthly_stat['date'] ) {
				continue;
			}

			// If the user has just requested stale stats.
			if ( $request->get_param( 'stale' ) ) {

				// If stale append to the returned result array.
				if ( isset( $monthly_stat['stale'] ) ) {
					$result[ $increment ] = $monthly_stat['stale'];
				}
			} else {

				// Get the array counts.
				$active   = isset( $monthly_stat['active'] ) ? $monthly_stat['active'] : false;
				$archived = isset( $monthly_stat['archived'] ) ? $monthly_stat['archived'] : false;

				if ( $active ) {
					$result['active'][] = $active;
				}

				if ( $archived ) {
					$result['archived'][] = $archived;
				}
			}

			$increment++;
		}

		return $result;
	}

	/**
	 * Format the data
	 */
	public function format_current_site_stats( $data ) {

		// CSV structure for the over all csv.
		$overall_stats = array(
			array(
				'Active',
				'Archived',
				'Stale',
			),
			array(
				$data['stats'][0],
				$data['stats'][1],
				$data['stats'][2],
			),
		);

		// CSV headers for the sites csv.
		$site_information = array(
			array(
				'name',
				'site_url',
				'status',
				'registered',
				'last_updated',
				'post_count',
			),
		);

		// loop through the sites and move them down an array level,
		// so it matches our csv function.
		foreach ( $data['sites'] as $key => $site ) {
			$site_information[] = $site;
		}

		// Setup the download array.
		$downloads = array(
			$site_information,
			$overall_stats,
		);

		// Download as two files csv files in a zip.
		$this->array_to_zipped_csv_download( $downloads, 'edupack_site_status_statistics.zip' );
	}

	/**
	 * Get the status over time data & format.
	 */
	public function get_status_over_time_download() {

		// Setup variables.
		$sites_data = array();
		$months     = array();
		$headers    = array(
			'Site Name',
			'Site URL',
		);

		// Get the last 12 months backwards from this month.
		for ( $i = 12; $i >= 1; $i-- ) {
			$month     = date( 'M', strtotime( date( 'Y-m-01' ) . - $i . ' months' ) );
			$months[]  = strtolower( $month );
			$headers[] = $month;
		}

		// setup the download array.
		$csv_download = array(
			$headers,
		);

		// Setup the args for grabbing the sites.
		$args = array(
			'limit'      => -1,
			'network_id' => get_current_network_id(),
		);

		// Loop the sites here.
		$sites = get_sites(
			$args
		);

		foreach ( $sites as $key => $site ) {

			// Empty the arrays from the previous loop.
			$site_data = array();
			$statuses  = array();

			// Switch site.
			switch_to_blog( $site->blog_id );

			// Get the option.
			$statuses = get_option( 'edupack_monthly_site_status' );

			// Get extra blog information.
			$blog_details = get_blog_details( $site->blog_id );

			// Set-up the site data for this site.
			$site_data = array(
				$blog_details->blogname,
				get_home_url( $site->blog_id ),
			);

			$status_count = count( $statuses );

			// Remove the last item if it's the current month.
			if ( $statuses && $status_count > 0 ) {

				$last_item_added = $statuses[ array_key_last( $statuses ) ];

				if ( $last_item_added ) {

					if ( date( 'Y-m' ) === $last_item_added['date'] ) {

						// Remove if this month already set.
						array_pop( $statuses );

						$status_count--;
					}
				}
			}

			if ( $status_count > 12 ) {
				array_slice( $statuses, -12 );
			} elseif ( $status_count < 12 ) {

				$fake_count = 12 - $status_count;

				for ( $i = 0; $i < $fake_count; $i++ ) {
					array_unshift( $statuses, array() );
				}
			}

			// Append the statuses.
			foreach ( $months as $key => $month ) {

				if ( isset( $statuses[ $key ]['month'] ) && $month === $statuses[ $key ]['month'] ) {
					$site_data[] = $statuses[ $key ]['status'];
				} else {
					$site_data[] = '—';
				}
			}

			// Append site data to list of all sites.
			$csv_download[] = $site_data;

			// Restore the current blog.
			restore_current_blog();
		}

		// Convert data to csv & download.
		$this->array_to_csv_download( $csv_download, 'edupack-site-status-over-time.csv' );
	}

	/**
	 * Download a csv of the current site statuses.
	 */
	public function download_dashboard_stats() {

		// Check the nonce.
		if ( ! isset( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'edulab-download-nonce' ) ) {
			return;
		}

		// Check the user perms
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Are we trying to download?
		if ( ! isset( $_GET['edupack_download'] ) || empty( $_GET['edupack_download'] ) ) {
			return;
		}

		// Dependant on the request get different data.
		switch ( $_GET['edupack_download'] ) {
			case 'current-status-stats':
				$data = $this->get_current_site_statuses( array( 'edupack_download_stats' => true ) );
				$data = $this->format_current_site_stats( $data );
				break;
			case 'activated-over-time-stats':
			case 'stale-over-time-stats':
				$data = $this->get_status_over_time_download();
				break;
		}

		exit();
	}
}

new Edupack_Dashboard_Handler();

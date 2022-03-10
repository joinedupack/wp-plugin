<?php
/**
 * Edupack Archival Policies Submit
 *
 * @package edupack
 * @version 1.0.0
 */

/**
 * Edupack admin keywords
 */
class Edupack_Archival_Policies_Handler extends Edupack {

	/**
	 * The class constructor
	 */
	public function __construct() {
		$this->add_filters();
		$this->add_actions();
		$this->add_remove_actions();
		$this->cron_jobs();
	}

	public function add_actions() {
		add_action( 'admin_post_edupack_archival_policies', array( $this, 'edupack_save_archival_policies' ) );
		add_action( 'wp_ajax_edupack_sites', array( $this, 'edupack_get_sites' ) );
		add_action( 'wp_login', array( $this, 'edupack_store_site_activity' ), 10, 2 );
		add_action( 'admin_init', array( $this, 'admin_load_save_activity' ), 10, 2 );

		if ( isset( $_GET['tab'] ) && 'archival-policies' === $_GET['tab'] || ! isset( $_GET['tab'] ) ) {
			add_action( 'admin_enqueue_scripts', array( $this, 'edupack_select2_enqueue' ) );
		}
	}

	public function add_filters() {}

	public function add_remove_actions() {}

	/**
	 * Register the cron jobs for settings
	 */
	public function cron_jobs() {
		// Don't register the cron jobs if it is not the root site.
		if ( get_main_network_id() !== get_current_blog_id() ) {
			return;
		}

		// Schedule the cron for warning emails
		if ( ! wp_next_scheduled( 'edupack_send_warning_emails' ) ) {
			wp_schedule_event( strtotime( 'midnight' ), 'twicedaily', 'edupack_send_warning_emails' );
		}
		add_action( 'edupack_send_warning_emails', array( $this, 'edupack_send_warning_emails' ) );

		// Schedule the cron for archiving sites
		if ( ! wp_next_scheduled( 'edupack_archive_sites' ) ) {
			wp_schedule_event( strtotime( 'midnight' ), 'twicedaily', 'edupack_archive_sites' );
		}
		add_action( 'edupack_archive_sites', array( $this, 'edupack_archive_sites' ) );
	}

	/**
	 * Enqueue the select2 field
	 */
	public function edupack_select2_enqueue() {
		wp_enqueue_style( 'select2', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/css/select2.min.css' );
		wp_enqueue_script( 'select2', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/js/select2.min.js', array( 'jquery' ), '1.0.0', true );

		// please create also an empty JS file in your theme directory and include it too
		wp_enqueue_script( 'submission-form', $this->get_dist_js_url( 'fields.js' ), array( 'jquery', 'select2' ), '1.0.0', true );

		wp_localize_script(
			'submission-form',
			'choices',
			array(
				'site_url' => get_site_url(),
			)
		);
	}

	/**
	 * Set the current site activity values
	 */
	public function set_site_activity_values( $site_id ) {
		update_site_meta( $site_id, 'edulab_site_activity', date( 'd-m-Y H:i:s' ) );
		update_site_meta( $site_id, 'edulab_warning_email_sent', '0' );
	}

	/**
	 * Store the site activity aginst the site
	 */
	public function admin_load_save_activity() {
		$this->set_site_activity_values( get_current_blog_id() );
	}

	/**
	 * Store the site activity aginst the site
	 */
	public function edupack_store_site_activity( $user_login, $user ) {
		$this->set_site_activity_values( get_current_blog_id() );
	}

	/**
	 * The Cron Job that fires out warning emails for sites
	 */
	public function edupack_send_warning_emails() {
		$sites = get_sites(
			array(
				'search' => esc_sql( $_GET['q'] ),
				'limit' => -1,
			)
		);

		$headers = array( 'Content-Type: text/html; charset=UTF-8' );

		foreach ( $sites as $site ) {
			// Check if it has expired. If so send the email.
			switch_to_blog( $site->blog_id );

			// Have we already sent the email?
			if ( '1' === get_site_meta( $site->blog_id, 'edulab_warning_email_sent', true ) ) {
				continue;
			}

			$excemptions = $this->get_setting_option( 'edupack_archival_policies', 'exemptions' ) ? $this->get_setting_option( 'edupack_archival_policies', 'exemptions' ) : array();

			// Have we been told to ignore this site?
			if ( in_array( $site->blog_id, $excemptions ) ) {
				continue;
			}

			$email = get_option( 'admin_email' );

			// If there is no email to send to.
			if ( ! $email ) {
				continue;
			}

			// Load all timestamp values
			$warning_email_metric = $this->get_setting_option( 'edupack_archival_policies', 'warning_email_period_metric' );
			$warning_email_period = $this->get_setting_option( 'edupack_archival_policies', 'warning_email_period' );
			$warning_timestamp = $this->get_timestamp_from_metric( $warning_email_period, $warning_email_metric, '-' );

			$inactive_email_metric = $this->get_setting_option( 'edupack_archival_policies', 'inactive_site_period_metric' );
			$inactive_email_period = $this->get_setting_option( 'edupack_archival_policies', 'inactive_site_period' );
			$inactive_timestamp = $this->get_timestamp_from_metric( $inactive_email_period, $inactive_email_metric );

			// Turn off email sending for now
			$site_activity = get_site_meta( $site->blog_id, 'edulab_site_activity', true );

			// Do we need to send the warning email
			if ( $site_activity && strtotime( $site_activity ) < strtotime( $warning_timestamp ) ) {

				// How long has it been inactive?
				$date_now = date_create( date( 'Y-m-d H:i:s' ) );
				$site_activity_for_diff = date_create( $site_activity );
				$warning_for = date_diff( $date_now, $site_activity_for_diff );

				// When will it de-activate?
				// It will deactive the last activity date + de-activation period 
				$inactive_formatted = date( 'Y-m-d H:i:s', strtotime( $site_activity . ' ' . $inactive_timestamp ) );
				$inactive_formatted_for_diff = date_create( $inactive_formatted );
				$inactive_for = date_diff( $date_now, $inactive_formatted_for_diff );

				// Build the email body
				$message = sprintf(
					'
					<p>Dear Site Administrator</p>
					<p>
						Your website %1$s has been inactive for %2$s.
						Your website is scheduled to automatically archive in %3$s.
						Logging into your site will prevent automatic archival.
					<p>
					<p>You can log in at <a href="%4$s">%4$s</a></p>',
					$this->get_blog_title( $site->blog_id ),
					$warning_for->format( '%a' ) . ' days',
					$inactive_for->format( '%a' ) . ' days',
					$this->get_admin_url( $site->blog_id ),
				);

				wp_mail( $email, 'Site Archive Warning', $message, $headers );
				update_site_meta( $site->blog_id, 'edulab_warning_email_sent', '1' );
			}

			restore_current_blog();
		}
	}

	/**
	 * Edupack Archive Sites
	 */
	public function edupack_archive_sites() {
		$sites = get_sites(
			array(
				'search' => esc_sql( $_GET['q'] ),
				'limit' => -1,
			)
		);

		foreach ( $sites as $site ) {
			switch_to_blog( $site->blog_id );

			// If it is already archived
			if ( 0 === (int) $site->public ) {
				continue;
			}

			$excemptions = $this->get_setting_option( 'edupack_archival_policies', 'exemptions' ) ? $this->get_setting_option( 'edupack_archival_policies', 'exemptions' ) : array();

			// Have we been told to ignore this site?
			if ( in_array( $site->blog_id, $excemptions ) ) {
				continue;
			}

			// Get all the inactive time periods
			$inactive_email_metric = $this->get_setting_option( 'edupack_archival_policies', 'inactive_site_period_metric' );
			$inactive_email_period = $this->get_setting_option( 'edupack_archival_policies', 'inactive_site_period' );
			$inactive_timestamp = $this->get_timestamp_from_metric( $inactive_email_period, $inactive_email_metric );

			// Get the site activity
			$site_activity = get_site_meta( $site->blog_id, 'edulab_site_activity', true );

			// Get the correct timestamps to compare
			$inactive_formatted = date( 'Y-m-d H:i:s', strtotime( $site_activity . ' ' . $inactive_timestamp ) );
			$now = date( 'Y-m-d H:i:s' );

			// Update the site to archived.
			if ( strtotime( $inactive_formatted ) < strtotime( $now ) ) {
				wp_update_site( $site->blog_id, array( 'public' => 0 ) );
			}

			restore_current_blog();
		}
	}

	/**
	 * Edupack, AJAX endpoint for getting a list of sites.
	 */
	public function edupack_get_sites() {
		$sites = get_sites(
			array(
				'search' => esc_sql( $_GET['q'] ),
				'limit' => -1,
			)
		);

		foreach ( $sites as $site ) {
			$sites_data[] = array(
				'key' => $site->blog_id,
				'value' => $this->get_blog_title( $site->blog_id ),
				'network_id' => get_current_blog_id(),
			);
		}

		echo json_encode( $sites_data );
		die;
	}


	/**
	 * Handle the form submission
	 */
	public function edupack_save_archival_policies() {
		// Check the nonce field
		$redirect_url = network_admin_url( 'admin.php?page=' . 'settings' . '&tab=archival-policies' );
		$flash = new Edupack_Flash_Message( 'archival_form_' );
		if ( empty( $_POST ) || ! wp_verify_nonce( $_POST['archival_policies_nonce'], 'archival_policies' ) ) {
			$flash->create_error_message( 'There was a critical error saving the form, please try again.' );
			wp_redirect( $redirect_url );
			exit;
		}

		$inactive_site_period = $this->get_post_field( 'inactive_site_period', true );
		$inactive_site_period_metric = $this->get_post_field( 'inactive_site_period_metric', true );

		$warning_email_period = $this->get_post_field( 'warning_email_period', true );
		$warning_email_period_metric = $this->get_post_field( 'warning_email_period_metric', true );

		$settings = array(
			'exemptions'                   => $this->get_post_field( 'exemptions', true ),
			'archiving_permissions'        => $this->get_post_field( 'allow_admins_to_archive', true ),
			'publishing_permissions'       => $this->get_post_field( 'allow_admins_to_publish', true ),
			'warning_email_period_metric'  => $warning_email_period_metric,
			'inactive_site_period_metric'  => $inactive_site_period_metric,
			'warning_email_period'         => $warning_email_period,
			'inactive_site_period'         => $inactive_site_period,
		);

		update_network_option( false, 'edupack_archival_policies', $settings );

		$flash->create_success_message( 'Settings have been saved succesfully' );

		wp_redirect( $redirect_url );
		exit;
	}
	/**
	 * Get label from metric and period 
	 *
	 * @param string $period - The period.
	 * @param string $metric - The metric for the period from now.
	 *
	 * @return string
	 */
	public function get_timestamp_label_from_metric( $period, $metric ) {
		$ts_metric = '';

		if ( ! $period ) {
			return '';
		}

		switch ( $metric ) {
			case 'weeks':
				$ts_metric = ' Week';
				break;
			case 'months':
				$ts_metric = ' Month';
				break;
			case 'years':
				$ts_metric = ' Year';
				break;
			default:
				'';
		}

		return $period . $ts_metric;
	}

	/**
	 * Get timestamp from metric and period 
	 *
	 * @param string $period - The period.
	 * @param string $metric - The metric for the period from now.
	 * @param string $diff - +/- the timestamp
	 * 
	 * @return string $timestamp
	 */
	public function get_timestamp_from_metric( $period, $metric, $diff = '+' ) {
		$ts_metric = '';

		if ( ! $period ) {
			return '';
		}

		switch ( $metric ) {
			case 'weeks':
				$ts_metric = 'weeks';
				break;
			case 'months':
				$ts_metric = 'month';
				break;
			case 'years':
				$ts_metric = 'year';
				break;
			default:
				'';
		}

		$strtotime_text = $diff . $period . ' ' . $ts_metric;

		return $strtotime_text;
	}
}

new Edupack_Archival_Policies_Handler();
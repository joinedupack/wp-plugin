<?php
/**
 * Edupack site owners
 *
 * Hanldes the site owner for edupack
 *
 * @package edupack
 * @version 1.0.0
 */

/**
 * Edupack site owner
 */
class Edupack_Site_Owner extends Edupack {

	/**
	 * The class constructor
	 */
	public function __construct() {
		$this->add_filters();
		$this->add_actions();
		$this->add_remove_actions();
	}

	/**
	 * Add actions for class
	 */
	public function add_actions() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_site_owner_choices' ) );
	}

	/**
	 * Add filters for class
	 */
	public function add_filters() {
		add_filter( 'gettext', array( $this, 'change_site_owner_text' ), 20, 3 );
		add_filter( 'gettext', array( $this, 'change_site_owner_description' ), 20, 3 );
	}

	/**
	 * Add removals for class
	 */
	public function add_remove_actions() {}

	/**
	 * Change the site owner text
	 * 
	 * @param string $translated_text
	 * @param string $untranslated_text
	 * @param string $domain
	 * 
	 * @return string $translated_text
	 */
	public function change_site_owner_text( $translated_text, $untranslated_text, $domain ) {
		if ( $untranslated_text === 'Administration Email Address' ) {
			$translated_text = __( 'Site Owner' );
		}

		return $translated_text;
	}

	/**
	 * Change the site owner description
	 * 
	 * @param string $translated_text
	 * @param string $untranslated_text
	 * @param string $domain
	 * 
	 * @return string $translated_text
	 */
	public function change_site_owner_description( $translated_text, $untranslated_text, $domain ) {
		if ( 'This address is used for admin purposes. If you change this we will send you an email at your new address to confirm it. <strong>The new address will not become active until confirmed.</strong>' === $untranslated_text ) {
			$translated_text = __( 'Site Owner must be selected from current site administrators. If you change this we will send you an email at your new address to confirm it. <strong>The new address will not become active until confirmed.</strong>' );
		}
		return $translated_text;
	}

	/**
	 * Enqueue and pass data to choices script on general options page.
	 * Legal values are emails corresponding to admin users on the current site.
	 *
	 * @param string $hook
	 */
	public function enqueue_site_owner_choices( $hook ) {
		if ( 'options-general.php' !== $hook ) {
			return;
		}

		$user_args = array( 
			'role'   => 'administrator',
			'fields' => array( 'ID', 'user_email' ),
		);
	
		$emails = get_users( $user_args );

		wp_enqueue_script( 'admin-choices', $this->get_dist_js_url( 'choices.min.js' ) );
		wp_enqueue_script( 'admin-choices-setup', $this->get_dist_js_url( 'choices-setup.js' ), array( 'jquery' ) );

		wp_localize_script( 
			'admin-choices-setup',
			'adminEmails',
			array(
				'emails' => $emails,
			)
		);
	}
	
}

new Edupack_Site_Owner();

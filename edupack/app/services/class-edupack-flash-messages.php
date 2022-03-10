<?php
/**
 * Edupack flash messages
 *
 * @package edupack
 * @version 1.0.0
 */

/**
 * Edupack network admin dashboard
 */
class Edupack_Flash_Message extends Edupack {

	var $success_transient = '';
	var $error_transient = '';
	var $message_key = '';

	/**
	 * The class constructor
	 */
	public function __construct( $message_key ) {
		$this->message_key = $message_key;

		// We use the user ID so each alert is specific to the user looking at it, otherwise we could have race conditions
		$this->success_transient = 'success_' . $message_key . get_current_user_id();
		$this->error_transient = 'error_' . $message_key . get_current_user_id();
	}

	/**
	 * Create the error message for a form
	 *
	 * @param string $message - The success message
	 */
	public function create_error_message( string $message ) {
		update_option( $this->error_transient, $message, 'no' );
	}

	/**
	 * Create the success message for a form
	 *
	 * @param string $message - The succcess message
	 */
	public function create_success_message( string $message ) {
		update_option( $this->success_transient, $message, 'no' );
	}

	/**
	 * Display success message
	 */
	public function display_error_message() {
		$message = get_option( $this->error_transient );
		
		if ( ! $message ) {
			return;
		}
		?>

		<div id="<?php esc_attr_e( $this->error_transient ); ?>" class="flash flash-message flash-error">
			<span class="flash-text"><?php esc_html_e( $message ); ?></span>
			<button class="dismiss" onclick='document.getElementById("<?php esc_attr_e( $this->error_transient ); ?>").remove();'>
				<img src="<?php echo esc_url( $this->get_asset_url( 'dismiss.svg' ) ); ?>"/>
			</button>
		</div>

		<?php
		delete_option( $this->error_transient );
	}

	/**
	 * Display success message
	 */
	public function display_success_message() {
		$message = get_option( $this->success_transient );
		
		if ( ! $message ) {
			return;
		}
		?>

		<div id="<?php esc_attr_e( $this->success_transient ); ?>" class="flash flash-message flash-success">
			<span class="flash-text"><?php esc_html_e( $message ); ?></span>
			<button class="dismiss" onclick='document.getElementById("<?php esc_attr_e( $this->success_transient ); ?>").remove();'>
				<img src="<?php echo esc_url( $this->get_asset_url( 'dismiss.svg' ) ); ?>"/>
			</button>
		</div>

		<?php
		delete_option( $this->success_transient );
	}

}
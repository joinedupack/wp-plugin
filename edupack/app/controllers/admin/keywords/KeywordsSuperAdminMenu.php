<?php

/**
 * Menu for adding and removing allowed site keywords globally.
 * This menu appears on the network admin dashboard.
 * Global site keywords are stored as a comma-separated list of values under the 'site-tags' network option.
 */
class KeywordsSuperAdminMenu {
	protected $slug = 'edupack_allowed_keywords';
	protected $assets_url;

	/**
	 * Constructor adds actions and sets $assets_url
	 *
	 * @uses "admin_enqueue_scripts" action
	 * @uses "admin_menu" action
	 */
	public function __construct( $assets_url ) {
		$this->assets_url = $assets_url;
		add_action( 'network_admin_menu', array( $this, 'add_to_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'register_assets' ) );
	}

	/**
	 * Adds admin menu
	 */
	public function add_to_menu() {
		$page_title          = 'Site Keywords';
		$menu_title          = 'Site Keywords';
		$required_capability = 'manage_options';
		$rendering_function  = array( $this, 'render_page' );
		if ( function_exists( 'add_submenu_page' ) ) {
			add_submenu_page(
				'settings.php',
				__( $page_title, 'edupack-text-domain' ),
				__( $menu_title, 'edupack-text-domain' ),
				$required_capability,
				$this->slug,
				$rendering_function
			);
		}
	}

	/**
	 * Register and localize CSS and JS for page
	 */
	public function register_assets() {
		wp_register_style( $this->slug, $this->assets_url . 'assets/dist/css/_legacy/keyword-manager.min.css' );
	}

	/**
	 * Enqueue CSS and JS for page
	 */
	protected function enqueue_assets() {
		if ( ! wp_script_is( $this->slug, 'registered' ) ) {
			$this->register_assets();
		}
		wp_enqueue_script( $this->slug );
		wp_enqueue_style( $this->slug );
	}

	/**
	 * Render the page
	 */
	public function render_page() {
	$this->enqueue_assets();
	$this->handle_keyword_updates( $_POST );
	$site_keywords = $this->get_site_keywords();
	?>
	<h1>Site Keyword Management (Network Admin)</h1>
	<h3>Current Keywords</h3>
	<form id="form-remove_keyword" method="post">
	<table id="keywords-table">
		<tbody>
		<?php if ( ! empty( $site_keywords ) ) : ?>
			<?php foreach ( $site_keywords as $keyword ) : ?>
			<tr class="table-row" id="row-<?php echo $keyword; ?>">
				<td class="row-keyword"><?php echo $keyword; ?></td>
				<td class="row-delete"><button type="submit" name="deletion" value="<?php echo $keyword; ?>">Remove</button></td>
			</tr>
			<?php endforeach; ?>
		<?php else : ?>
			<tr class="table-row" id="row-no_keywords">
			<td class="row-keyword">No keywords found!</td>
			</tr>
		<?php endif; ?>
		</tbody>
	</table>
	</form>
	
	<h3>Add New Keyword</h3>
	<form id="form-add_keyword" method="post">
	<input type="text" name="insertion" id="input-add_keyword">
	<button type="submit">Add</button>
	</form>      

	<?php
	}

	/**
	 * Handle site keyword insertion and deletion requests from super admin interface
	 */
	protected function handle_keyword_updates( $post_data ) {
		if ( isset( $post_data['insertion'] ) ) {
		$this->add_site_keyword( $post_data['insertion'] );
		}

		if ( isset( $post_data['deletion'] ) ) {
		$this->delete_site_keyword( $post_data['deletion'] );
		}
	}

	/**
	 * Delete this nephew
	 */
	protected function delete_site_keyword( $keyword_to_delete ) {
		$keywords          = $this->get_site_keywords();
		$keyword_to_delete = $this->sanitize_keyword( $keyword_to_delete );

		// Nothing to delete if no matching keyword found
		$key = array_search( $keyword_to_delete, $keywords );
		if ( $key !== false ) {
			unset( $keywords[ $key ] );
			$keywords = implode( ',', $keywords );
			update_network_option( null, 'site-tags', $keywords );
		}
	}

	/**
	 * Add new keyword
	 */
	protected function add_site_keyword( $keyword_to_add ) {
		$keywords       = $this->get_site_keywords();
		$keyword_to_add = $this->sanitize_keyword( $keyword_to_add );

		// Don't add if keyword already exists in site keywords
		if ( ! empty( $keywords ) && in_array( $keyword_to_add, $keywords ) ) {
			return;
		}

		// Push to keywords, serialize, and save
		$keywords[] = $keyword_to_add;
		$keywords   = implode( ',', $keywords );
		update_network_option( null, 'site-tags', $keywords );

	}

	/**
	 * Get keywords as array. Returns an empty array if none found.
	 */
	protected function get_site_keywords() {
		$site_keywords = get_network_option( null, 'site-tags', array() );
		if ( $site_keywords ) {
			$site_keywords = explode( ',', $site_keywords );
		} else {
			$site_keywords = array();
		}
		return $site_keywords;
	}

	/**
	 * Trim and sanitize keywords -- only allow alphanumeric values and spaces up to 64 chars long
	 */
	protected function sanitize_keyword( $keyword ) {
		$keyword = preg_replace( '/[^a-zA-Z0-9\s]/', '', $keyword );
		$keyword = substr( $keyword, 0, 32 );
		return $keyword;
	}
}

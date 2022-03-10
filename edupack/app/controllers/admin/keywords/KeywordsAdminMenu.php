<?php

/**
 * Menu for choosing site keywords that apply to the site.
 * This menu appears on subsite dashboards.
 * Site keywords are stored as a comma-separated list of values under the 'site-tags' meta key.
 * Allowed keywords are pulled from the 'site-tags' network option.
 */
class KeywordsAdminMenu {
	protected $slug = 'edupack_site_keywords';
	protected $assets_url;

	/**
	 * Constructor adds actions and sets $assets_url
	 *
	 * @uses "admin_enqueue_scripts" action
	 * @uses "admin_menu" action
	 */
	public function __construct( $assets_url ) {
		$this->assets_url = $assets_url;
		add_action( 'admin_menu', array( $this, 'add_to_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'register_assets' ) );
	}

	/**
	 * Adds admin menu
	 */
	public function add_to_menu() {
		$page_title          = 'Site Keywords';
		$menu_title          = 'Site Keywords';
		$required_capability = 'edit_theme_options';
		$rendering_function  = array( $this, 'render_page' );
		if ( function_exists( 'add_options_page' ) ) {
			add_options_page(
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
		wp_register_style( $this->slug, $this->assets_url . 'dist/css/_legacy/keyword-manager.min.css' );
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
		$site_keywords    = $this->get_site_keywords();
		$network_keywords = $this->get_network_keywords();
			?>
		<h1>Site Keyword Management</h1>
		<p>On this page, you can choose which <b>keywords</b> (or <b>site tags</b>) apply to this site.</p>

		<h3>Keywords</h3>
			<form id="form-update_keywords" method="post">
			<table id="keywords-table">
				<tbody>
				<?php if ( ! empty( $network_keywords ) ) : ?>
					<tr>
					<th>Keyword</th>
					<th>Enabled</th>
					</tr>
					<?php foreach ( $network_keywords as $keyword ) : ?>
					<?php $enabled = in_array( $keyword, $site_keywords ); ?>
					<tr class="table-row" id="row-<?php echo $keyword; ?>">
						<td class="row-keyword"><?php echo $keyword; ?></td>
						<td class="row-enabled"><input type="checkbox" name="keywords[]" value="<?php echo $keyword; ?>" 
							<?php
							if ( $enabled ) {
								echo 'checked';
							}
							?>
						></td>
					</tr>
					<?php endforeach; ?>
				<?php else : ?>
					<tr class="table-row" id="row-<?php echo $keyword; ?>">
						<td class="row-keyword">No keywords found!</td>
					</tr>
				<?php endif; ?>
				</tbody>
			</table>
			<input id="submit-keyword_update" type="submit" class="button" value="Update Keywords">
		</form> 

			<?php
	}

	/**
	 * Handle site keyword insertion and deletion requests from admin interface
	 */
	protected function handle_keyword_updates( $post_data ) {
		if ( isset( $post_data['keywords'] ) ) {
			$this->update_site_keywords( $post_data['keywords'] );
			}
	}

	/**
	 * Set site keywords to the given list of values.
	 * Only adds network-enabled keywords.
	 * Filters, serializes, then saves.
	 */
	protected function update_site_keywords( $keywords ) {
		$network_keywords = $this->get_network_keywords();
		$keywords         = array_intersect( $keywords, $network_keywords );
		$keywords         = implode( ',', $keywords );
		update_site_meta( get_current_blog_id(), 'site-tags', $keywords );
	}

	/**
	 * Get site keywords as array. Returns an empty array if none found.
	 */
	protected function get_site_keywords() {
		$site_keywords = get_site_meta( get_current_blog_id(), 'site-tags', true );
		if ( $site_keywords ) {
	$site_keywords = explode( ',', $site_keywords );
			} else {
	$site_keywords = array();
			}
		return $site_keywords;
	}

	/**
	 * Get network keywords as array. Returns an empty array if none found.
	 */
	protected function get_network_keywords() {
		$site_keywords = get_network_option( null, 'site-tags', array() );
		if ( $site_keywords ) {
			$site_keywords = explode( ',', $site_keywords );
		} else {
			$site_keywords = array();
		}
		return $site_keywords;
	}

	/**
	 * Trim and sanitize keywords -- only allow alphanumeric values and spaces up to 32 chars long
	 */
	protected function sanitize_keyword( $keyword ) {
		$keyword = preg_replace( '/[^a-zA-Z0-9\s]/', '', $keyword );
		$keyword = substr( $keyword, 0, 32 );
		return $keyword;
	}
}

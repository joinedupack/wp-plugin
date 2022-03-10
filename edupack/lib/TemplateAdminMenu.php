<?php

class TemplateAdminMenu {
	protected $slug = 'edupack_templater';
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
		$page_title          = 'Site Templates';
		$menu_title          = 'Site Templates';
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
		wp_register_script( $this->slug, $this->assets_url . 'assets/dist/js/template-manager.js', array( 'jquery' ) );
		wp_register_style( $this->slug, $this->assets_url . 'assets/dist/css/template-manager.css' );
		wp_localize_script(
			$this->slug,
			'EDUPACK',
			array(
				'strings' => array(
					'updated' => __( 'Templates updated!', 'edupack-text-domain' ),
					'built'   => __( 'New site built!', 'edupack-text-domain' ),
					'error'   => __( 'An error has occurred. Please try again later.', 'edupack-text-domain' ),
				),
				'api'     => array(
					'templates_url'     => esc_url_raw( rest_url( 'edupack-api/v1/templates' ) ),
					'all_templates_url' => esc_url_raw( rest_url( 'edupack-api/v1/templates/all' ) ),
					'build_url'         => esc_url_raw( rest_url( 'edupack-api/v1/templates/builder' ) ),
					'nonce'             => wp_create_nonce( 'wp_rest' ),
				),
			)
			);
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
		$network      = network_home_url();
		$base_domain  = parse_url( $network, PHP_URL_HOST );
		$args         = array( 
			'domain' => $base_domain,
		);
		$sites     = get_sites( $args );
		$templates = TemplateFunctions::get_templates();
		$map_ids   = function( $val ) {
			return $val->blog_id;
		};
		if ( ! empty( $templates ) ) {
			$template_ids = array_map( $map_ids, $templates );
		} else {
			$template_ids = array();
		}
		?>
		<h1>Edupack Multisite Template Management</h1>
		<h3>Current templates</h3>
			<table id="templates">
				<tbody>
					<tr>
						<th>Site ID</th>
						<th>Site URL</th>
						<th>Site Name</th>
						<th>Template</th>
					</tr>
					<?php if ( ! empty( $sites ) ) : ?>
						<?php foreach ( $sites as $site ) : ?>
							<?php $is_temp = in_array( $site->blog_id, $template_ids ); ?>
							<tr class="site-row" id='site-<?php echo $site->blog_id; ?>'>
								<td class="site-id"><?php echo $site->blog_id; ?></td>
								<td class="site-url"><?php echo $site->domain; ?></td>
								<td class="site-name"><?php echo $site->blogname; ?></td>
								<td class="site-is_template"><input type="checkbox" 
								<?php
								if ( $is_temp ) {
									echo 'checked';
								}
								?>
								></td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
		<input id="submit-template_update" type="button" class="button" value="Update Templates">
		<div id="template_update-response" class="response-container"></div>
		<?php
	}
}

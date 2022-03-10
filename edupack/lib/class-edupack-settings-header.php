<?php
/**
 * Edupack network admin dashboard
 *
 * Hanldes the main settings dashboard for edupack network settings
 *
 * @package edupack
 * @version 1.0.0
 */

/**
 * Edupack network admin dashboard
 */
class Edupack_Settings_Header extends Edupack {

    /**
     * The set of tabs 
     *
     * @var array $tabs
     */
    var $tabs = array();

    /**
     * The current tab
     *
     * @var string $current_tab
     */
    var $current_tab = '';

    /**
     * The current pagename
     *
     * @var string $pagename
     */
    var $pagename = '';

    /**
     * The the page url
     *
     * @var string $url
     */
    var $url = '';

    /**
	 * The class constructor
     *
     * @param array $tabs - An array of tabs
     * @param string $pagename - The current admin pagename used for URL
     * @param string $filepath - The filepath to the tabs templates in the templates folder
     * @param string $current_tab - The current tab in the request
     * @param string $default_tab - The default tab, used when tab $current_tab is empty and we have to force a tab.
	 */
	public function __construct( $tabs, $pagename, $filepath, $current_tab, $default_tab ) {
        $this->set_tabs( $tabs );
        $this->set_pagename( $pagename );
        $this->set_url();
        $this->set_current_tab( $current_tab, $default_tab );
        $this->set_file_path( $filepath );
	}

    /**
     * Get the file path 
     *
     * @return string
     */
    public function get_file_path() {
        return $this->filepath;
    }

    /**
     * Set the filepath
     *
     * @param string $filepath - The filepath.
     */
    public function set_file_path( $filepath ) {
        $this->filepath = $filepath;
    }

    /**
     * Sets the base URL
     */
    public function set_url() {
        $this->url = network_admin_url( 'admin.php?page=' . $this->get_pagename() . '&tab=' );
    }

    /**
     * Get a given tabs URL 
     *
     * @param string $tab - The tab name
     *
     * @return string - The URL for given tab
     */
    public function get_url( $tab ) {
        return $this->url . $tab;
    }

    /**
     * Sets the tabs 
     *
     * @param array $tabs
     */
    public function set_tabs( $tabs ) : void {
        $this->tabs = $tabs;

        return;
    }

    /**
     * Gets tabs
     * 
     * @return array
     */
    public function get_tabs() {
        return $this->tabs;
    }

    /**
     * Sets the pagename 
     *
     * @param string $pagename
     */
    public function set_pagename( $pagename ) {
        $this->pagename = $pagename;

        return;
    }

    /**
     * @return string
     */
    public function get_pagename() : string {
        return $this->pagename;
    }

    /**
     * @return string
     */
    public function get_current_tab() {
        return $this->current_tab;
    }

    /**
     * Set the current tab based of the given params
     * 
     * @param string $tab - The current tab, usually $_GET['tab']
     * @param string $default - The default tab, fallback if $tab is empty
     * 
     * @return void
     */
    public function set_current_tab( $tab, $default ) : void  {
        if ( ! $tab ) {
            $this->current_tab = $default;

            return;
        }

        $this->current_tab = $tab;
        return;
    }

    /**
     * Output the tabs template
     */
    public function output_template() {
        if ( $this->get_current_tab() ) {
            echo '<div id="edupack--options"><div class="container">';
                $this->include_admin_template( $this->get_file_path() . $this->get_current_tab() . '.php' );
            echo '</div></div>';
        }
    }

    /**
     * Output the header section
     */
    public function header() {
        ?>
        <div id="edupack--masthead">
            <?php $this->output_top_header(); ?>
        </div>
        <div id="edupack--tabs">
            <?php $this->output_tabs(); ?>
        </div>
        <?php
    }

    /**
     * Output the topheader content
     */
    public function output_top_header() {
        ?>
        <div class="logo">
            <h1>Edupack</h1>
        </div>
        <?php
    }

    /**
     * @param string $filename - The current filename
     * 
     * @return string - The class name
     */
    public function is_tab_active( $filename ) {
        if ( $this->get_current_tab() === $filename ) {
            return 'edupack--tab__active';
        }

        return '';
    }

    /**
     * Output the tabs in the header
     */
    public function output_tabs() {
        ?>
        <ul>
            <?php foreach( $this->get_tabs() as $title => $filename ) : ?>
                <li class="edupack--tab">
                    <a
                        class="<?php echo esc_attr( $this->is_tab_active( $filename ) ); ?>"
                        href="<?php echo esc_url( $this->get_url( $filename ) ); ?>
                    ">
                        <?php esc_html_e( $title ); ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>        
        <?php
    }
}
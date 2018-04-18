<?php

require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes\class-bibcite-logger.php';
require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes\class-bibcite-renderer.php';
require plugin_dir_path( dirname( __FILE__ ) ) . 'vendor\autoload.php';

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    Bibcite_SC/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines callbacks for admin-specific hooks.
 *
 * @package Bibcite_SC/admin
 * @author Keith Houston <keith@shadycharacters.co.uk>
 */
class Bibcite_SC_Admin {

	/**
	 * Defines the slug for the menu.
	 */
	private const MENU_SLUG = BIBCITE_SC_PREFIX . "_MENU";

	/**
	 * Groups all settings for this plugin.
	 */
	private const SETTINGS_GROUP = BIBCITE_SC_PREFIX . "_SETTINGS_GROUP";

	/**
	 * Identifies settings related to [bibshow] and [bibcite] shortcodes.
	 */
	private const BIBSHOW_SECTION = BIBCITE_SC_PREFIX . "_BIBSHOW_SECTION";

	/**
	 * Identifies settings related to [bibtex] shortcodes.
	 */
	private const BIBTEX_SECTION = BIBCITE_SC_PREFIX . "_BIBTEX_SECTION";

	/**
	 * The URLs of the default library or libraries to be used by this plugin.
	 */
	public const LIBRARY_URLS = BIBCITE_SC_PREFIX . "_LIBRARY_URLS";

	/**
	 * Holds the bibliographic style (IEEE, Chicago, etc.) of citations emitted at the end of a 
	 * [bibshow] shortcode.
	 */
	public const BIBSHOW_STYLE_NAME = BIBCITE_SC_PREFIX . "_BIBSHOW_STYLE_NAME";

	/**
	 * Holds the bibliographic style (IEEE, Chicago, etc.) of citations emitted at a [bibcite] 
	 * shortcode.
	 */
	public const BIBCITE_STYLE_NAME = BIBCITE_SC_PREFIX . "_BIBCITE_STYLE_NAME";

	/**
	 * Holds the bibliographic style (IEEE, Chicago, etc.) of citations emitted by a [bibtex] 
	 * shortcode.
	 */
	public const BIBTEX_STYLE_NAME = BIBCITE_SC_PREFIX . "_BIBTEX_STYLE_NAME";

	/**
	 * Holds the name of the template used to render the bibliography emitted at the end of a
	 * [bibshow] shortcode.
	 */
	public const BIBSHOW_TEMPLATE_NAME = BIBCITE_SC_PREFIX . "_BIBSHOW_TEMPLATE_NAME";

	/**
	 * Holds the name of the template used to render the reference emitted by a [bibcite] 
	 * shortcode.
	 */
	public const BIBCITE_TEMPLATE_NAME = BIBCITE_SC_PREFIX . "_BIBCITE_TEMPLATE_NAME";

	/**
	 * Holds the name of the template used to render the bibliography emitted by a [bibtex] 
	 * shortcode.
	 */
	public const BIBTEX_TEMPLATE_NAME = BIBCITE_SC_PREFIX . "_BIBTEX_TEMPLATE_NAME";

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $bibcite_sc    The ID of this plugin.
	 */
	private $bibcite_sc;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $bibcite_sc       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $bibcite_sc, $version ) {

		$this->bibcite_sc = $bibcite_sc;
		$this->version = $version;

	}

	/**
	 * Called in response to the 'admin_menu' hook. Used to create a menu for this plugin.
	 *
	 * @return void
	 * @author Keith Houston <keith@shadycharacters.co.uk>
	 * @since 1.0.0
	 */
	public function menu() {

		add_options_page( 
			'Bibcite SC settings', 				// Browser page title
			'Bibcite SC', 						// Menu title
			'manage_options', 					// required capability
			Bibcite_SC_Admin::MENU_SLUG,		// menu slug
			array( $this, 'do_options_page' ) 	// method to handle the menu
		);
	}

	/**
	 * Called in response to the 'admin_init' hook. Used to register settings for this plugin.
	 *
	 * @return void
	 * @author Keith Houston <keith@shadycharacters.co.uk>
	 * @since 1.0.0
	 */
	public function init() {

		// Citation styles for [bibshow] and [bibcite] shortcodes
		register_setting( Bibcite_SC_Admin::SETTINGS_GROUP, Bibcite_SC_Admin::BIBSHOW_STYLE_NAME );

		add_settings_section( 
			Bibcite_SC_Admin::BIBSHOW_SECTION, 	// section ID
			'Notes', 							// section title
			array($this, 'do_bibshow_section'),	// section header callback
			Bibcite_SC_Admin::MENU_SLUG			// parent menu slug
		);

		add_settings_field( 
			Bibcite_SC_Admin::BIBSHOW_STYLE_NAME,			// field ID
			'Bibliography citation style', 					// field title
			array ($this, 'do_bibshow_style_chooser' ), 	// field callback
			Bibcite_SC_Admin::MENU_SLUG, 					// parent menu slug
			Bibcite_SC_Admin::BIBSHOW_SECTION 				// parent section ID
		);
	}

	/**
	 * Callback used to populate our options page.
	 *
	 * @return void
	 * @author Keith Houston <keith@shadycharacters.co.uk>
	 * @since 1.0.0
	 */
	public function do_options_page () { ?>
    <div class="wrap">
        <h2>Bibcite SC settings</h2>
        <form action="options.php" method="POST">
            <?php settings_fields( Bibcite_SC_Admin::SETTINGS_GROUP ); ?>
            <?php do_settings_sections( Bibcite_SC_Admin::MENU_SLUG ); ?>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

	/**
	 * Callback used to populate our [bibshow] and [bibcite] section.
	 *
	 * @return void
	 * @author Keith Houston <keith@shadycharacters.co.uk>
	 * @since 1.0.0
	 */
	public function do_bibshow_section () {
		echo "Manage styles and templates for [bibshow] and [bibcite] shortcodes.";
	}

	/**
	 * Callback used to populate the setting UI for our [bibshow] style.
	 *
	 * @return void
	 * @author Keith Houston <keith@shadycharacters.co.uk>
	 * @since 1.0.0
	 */
	function do_bibshow_style_chooser () {

		// Create the input control itself
		$setting = esc_attr( get_option( Bibcite_SC_Admin::BIBSHOW_STYLE_NAME ) );
		$name = Bibcite_SC_Admin::BIBSHOW_STYLE_NAME;
		echo "<input list='$name' name='$name'/>";
		echo "<p class='description'><abbr title='Citation style language'>CSL</abbr> style for bibliography entries</p>";

		// Create its list of values
		echo "<datalist id='$name'>";
		$bibshow_style_names = Bibcite_Renderer::instance()->getCslStyleNames();
		foreach ($bibshow_style_names as $bibshow_style_name)
			echo "<option value='$bibshow_style_name'/>";
		echo "</datalist>";
	}
}
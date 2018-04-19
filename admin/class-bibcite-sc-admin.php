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

		// Settings
		register_setting(Bibcite_SC_Admin::SETTINGS_GROUP, Bibcite_SC_Admin::BIBSHOW_STYLE_NAME);
		register_setting(Bibcite_SC_Admin::SETTINGS_GROUP, Bibcite_SC_Admin::BIBSHOW_TEMPLATE_NAME);
		register_setting(Bibcite_SC_Admin::SETTINGS_GROUP, Bibcite_SC_Admin::BIBCITE_STYLE_NAME);
		register_setting(Bibcite_SC_Admin::SETTINGS_GROUP, Bibcite_SC_Admin::BIBCITE_TEMPLATE_NAME);
		register_setting(Bibcite_SC_Admin::SETTINGS_GROUP, Bibcite_SC_Admin::BIBTEX_STYLE_NAME);
		register_setting(Bibcite_SC_Admin::SETTINGS_GROUP, Bibcite_SC_Admin::BIBTEX_TEMPLATE_NAME);

		// Sections
		add_settings_section( 
			Bibcite_SC_Admin::BIBSHOW_SECTION, 	// section ID
			'Notes', 							// section title
			array($this, 'do_bibshow_section'),	// section header callback
			Bibcite_SC_Admin::MENU_SLUG			// parent menu slug
		);

		add_settings_section( 
			Bibcite_SC_Admin::BIBTEX_SECTION, 	// section ID
			'Bibliographies', 					// section title
			array($this, 'do_bibtex_section'),	// section header callback
			Bibcite_SC_Admin::MENU_SLUG			// parent menu slug
		);

		// Fields
		add_settings_field( 
			Bibcite_SC_Admin::BIBSHOW_STYLE_NAME,			// field ID
			'Bibliography entry style', 					// field title
			array ($this, 'do_bibshow_style_chooser' ), 	// field callback
			Bibcite_SC_Admin::MENU_SLUG, 					// parent menu slug
			Bibcite_SC_Admin::BIBSHOW_SECTION 				// parent section ID
		);

		add_settings_field( 
			Bibcite_SC_Admin::BIBSHOW_TEMPLATE_NAME,		// field ID
			'Bibliography list template', 					// field title
			array ($this, 'do_bibshow_template_chooser' ), 	// field callback
			Bibcite_SC_Admin::MENU_SLUG, 					// parent menu slug
			Bibcite_SC_Admin::BIBSHOW_SECTION 				// parent section ID
		);

		add_settings_field( 
			Bibcite_SC_Admin::BIBCITE_STYLE_NAME,			// field ID
			'Note entry style', 							// field title
			array ($this, 'do_bibcite_style_chooser' ), 	// field callback
			Bibcite_SC_Admin::MENU_SLUG, 					// parent menu slug
			Bibcite_SC_Admin::BIBSHOW_SECTION 				// parent section ID
		);

		add_settings_field( 
			Bibcite_SC_Admin::BIBCITE_TEMPLATE_NAME,		// field ID
			'Note list template', 							// field title
			array ($this, 'do_bibcite_template_chooser' ), 	// field callback
			Bibcite_SC_Admin::MENU_SLUG, 					// parent menu slug
			Bibcite_SC_Admin::BIBSHOW_SECTION 				// parent section ID
		);

		add_settings_field( 
			Bibcite_SC_Admin::BIBTEX_STYLE_NAME,			// field ID
			'Bibliography citation style', 					// field title
			array ($this, 'do_bibtex_style_chooser' ), 		// field callback
			Bibcite_SC_Admin::MENU_SLUG, 					// parent menu slug
			Bibcite_SC_Admin::BIBTEX_SECTION 				// parent section ID
		);

		add_settings_field( 
			Bibcite_SC_Admin::BIBTEX_TEMPLATE_NAME,			// field ID
			'Bibliography list template', 					// field title
			array ($this, 'do_bibtex_template_chooser' ), 	// field callback
			Bibcite_SC_Admin::MENU_SLUG, 					// parent menu slug
			Bibcite_SC_Admin::BIBTEX_SECTION 				// parent section ID
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
		echo "Manage styles and templates for notes and bibliographies generated by [bibshow] and [bibcite] shortcodes.";
	}

	/**
	 * Callback used to populate our [bibtex] section.
	 *
	 * @return void
	 * @author Keith Houston <keith@shadycharacters.co.uk>
	 * @since 1.0.0
	 */
	public function do_bibtex_section () {
		echo "Manage styles and templates for standalone bibliographies generated by [bibtex] shortcodes.";
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
		$id = Bibcite_SC_Admin::BIBSHOW_STYLE_NAME;
		echo "<input list='bibshow-style-names' name='$id' id='$id' value='$setting'/>";
		echo "<p class='description' title='Citation style language'><a href='http://citationstyles.org/'>CSL</a> style for individual bibliography entries</p>";

		// Create its list of values
		$bibshow_style_names = Bibcite_Renderer::instance()->getCslStyleNames();
		echo "<datalist id='bibshow-style-names'>";
		foreach ($bibshow_style_names as $bibshow_style_name)
			echo "<option value='$bibshow_style_name'/>";
		echo "</datalist>";
	}

	/**
	 * Callback used to populate the setting UI for our [bibshow] template.
	 *
	 * @return void
	 * @author Keith Houston <keith@shadycharacters.co.uk>
	 * @since 1.0.0
	 */
	function do_bibshow_template_chooser () {

		// Create the input control itself
		$setting = esc_attr( get_option( Bibcite_SC_Admin::BIBSHOW_TEMPLATE_NAME ) );
		$id = Bibcite_SC_Admin::BIBSHOW_TEMPLATE_NAME;
		echo "<input list='bibshow-template-names' name='$id' id='$id' value='$setting'/>";
		echo "<p class='description'><a href='https://twig.symfony.com/'>Twig</a> template for a list of styled entries</p>";

		// Create its list of values
		$bibshow_template_names = Bibcite_Renderer::instance()->getTwigTemplateNames();
		echo "<datalist id='bibshow-template-names'>";
		foreach ($bibshow_template_names as $bibshow_template_name)
			echo "<option value='$bibshow_template_name'/>";
		echo "</datalist>";
	}

	/**
	 * Callback used to populate the setting UI for our [bibcite] style.
	 *
	 * @return void
	 * @author Keith Houston <keith@shadycharacters.co.uk>
	 * @since 1.0.0
	 */
	function do_bibcite_style_chooser () {

		// Create the input control itself
		$setting = esc_attr( get_option( Bibcite_SC_Admin::BIBCITE_STYLE_NAME ) );
		$id = Bibcite_SC_Admin::BIBCITE_STYLE_NAME;
		echo "<input list='bibcite-style-names' name='$id' id='$id' value='$setting'/>";
		echo "<p class='description' title='Citation style language'><a href='http://citationstyles.org/'>CSL</a> style for individual note entries</p>";

		// Create its list of values
		$bibcite_style_names = Bibcite_Renderer::instance()->getCslStyleNames();
		echo "<datalist id='bibcite-style-names'>";
		foreach ($bibcite_style_names as $bibcite_style_name)
			echo "<option value='$bibcite_style_name'/>";
		echo "</datalist>";
	}

	/**
	 * Callback used to populate the setting UI for our [bibcite] template.
	 *
	 * @return void
	 * @author Keith Houston <keith@shadycharacters.co.uk>
	 * @since 1.0.0
	 */
	function do_bibcite_template_chooser () {

		// Create the input control itself
		$setting = esc_attr( get_option( Bibcite_SC_Admin::BIBCITE_TEMPLATE_NAME ) );
		$id = Bibcite_SC_Admin::BIBCITE_TEMPLATE_NAME;
		echo "<input list='bibcite-template-names' name='$id' id='$id' value='$setting'/>";
		echo "<p class='description'><a href='https://twig.symfony.com/'>Twig</a> template for a list of styled entries rendered as a single note</p>";

		// Create its list of values
		$bibcite_template_names = Bibcite_Renderer::instance()->getTwigTemplateNames();
		echo "<datalist id='bibcite-template-names'>";
		foreach ($bibcite_template_names as $bibcite_template_name)
			echo "<option value='$bibcite_template_name'/>";
		echo "</datalist>";
	}

	/**
	 * Callback used to populate the setting UI for our [bibtex] style.
	 *
	 * @return void
	 * @author Keith Houston <keith@shadycharacters.co.uk>
	 * @since 1.0.0
	 */
	function do_bibtex_style_chooser () {

		// Create the input control itself
		$setting = esc_attr( get_option( Bibcite_SC_Admin::BIBTEX_STYLE_NAME ) );
		$id = Bibcite_SC_Admin::BIBTEX_STYLE_NAME;
		echo "<input list='bibtex-style-names' name='$id' id='$id' value='$setting'/>";
		echo "<p class='description' title='Citation style language'><a href='http://citationstyles.org/'>CSL</a> style for bibliography entries</p>";

		// Create its list of values
		$bibtex_style_names = Bibcite_Renderer::instance()->getCslStyleNames();
		echo "<datalist id='bibtex-style-names'>";
		foreach ($bibtex_style_names as $bibtex_style_name)
			echo "<option value='$bibtex_style_name'/>";
		echo "</datalist>";
	}

	/**
	 * Callback used to populate the setting UI for our [bibtex] template.
	 *
	 * @return void
	 * @author Keith Houston <keith@shadycharacters.co.uk>
	 * @since 1.0.0
	 */
	function do_bibtex_template_chooser () {

		// Create the input control itself
		$setting = esc_attr( get_option( Bibcite_SC_Admin::BIBTEX_TEMPLATE_NAME ) );
		$id = Bibcite_SC_Admin::BIBTEX_TEMPLATE_NAME;
		echo "<input list='bibtex-template-names' name='$id' id='$id' value='$setting'/>";
		echo "<p class='description'><a href='https://twig.symfony.com/'>Twig</a> template for bibliography list</p>";

		// Create its list of values
		$bibtex_template_names = Bibcite_Renderer::instance()->getTwigTemplateNames();
		echo "<datalist id='bibtex-template-names'>";
		foreach ($bibtex_template_names as $bibtex_template_name)
			echo "<option value='$bibtex_template_name'/>";
		echo "</datalist>";
	}
}
<?php

namespace Bibcite\Admin;

require plugin_dir_path(dirname(__FILE__)) . 'vendor\autoload.php';

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://github.com/OrkneyDullard/bibcite
 * @since      1.0.0
 *
 * @package    Bibcite/Admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines public constants for WordPress options related to this plugin and creates an admin page 
 * to manage them.
 *
 * @package Bibcite/Admin
 * @author Keith Houston <keith@shadycharacters.co.uk>
 */
class Admin
{	
	/**
     * The URLs of the default library to be used by this plugin.
     */
    public const LIBRARY_URL = BIBCITE_SC_PREFIX . "_LIBRARY_URL";
	
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
    
    // Defines the slug for the menu.
	private const MENU_SLUG = BIBCITE_SC_PREFIX . "_MENU";
	   
    // Groups all settings for this plugin.
    private const SETTINGS_GROUP = BIBCITE_SC_PREFIX . "_SETTINGS_GROUP";
    
    // Identifies settings related to our default library.
	private const LIBRARY_SECTION = BIBCITE_SC_PREFIX . "_LIBRARY_SECTION";

    // Identifies settings related to [bibshow] and [bibcite] shortcodes.
	private const BIBSHOW_SECTION = BIBCITE_SC_PREFIX . "_BIBSHOW_SECTION";
	
	// Identifies settings related to [bibtex] shortcodes.
    private const BIBTEX_SECTION = BIBCITE_SC_PREFIX . "_BIBTEX_SECTION";
    
    // Identifies our custom "clear cache" action.
    private const CLEAR_CACHE_ACTION = BIBCITE_SC_PREFIX . "_CLEAR_CACHE";
	
	// The ID of this plugin.
	private $bibcite_sc;

    // The version of this plugin.
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @param string $bibcite_sc plugin ID
     * @param string $version plugin version
     * @author Keith Houston <keith@shadycharacters.co.uk>
     * @since 1.0.0
     */
    public function __construct(string $bibcite_sc, string $version)
    {

        $this->bibcite_sc = $bibcite_sc;
        $this->version = $version;

        // Handle custom POST messages by firing our custom 'clear_cache' hook.
        add_action( 
            "admin_post_" . self::CLEAR_CACHE_ACTION,
            function () { 
                \Bibcite\Common\Logger::instance()->debug(
                    "Received custom " . self::CLEAR_CACHE_ACTION . " action"
                );
                do_action(BIBCITE_SC_CLEAR_CACHE_ACTION); 
                wp_redirect(admin_url('options-general.php?page=' . self::MENU_SLUG));
                die(__FILE__);
            }
        );
        
    }

    /**
     * Set option defaults.
     *
     * @return void
     * @author Keith Houston <keith@shadycharacters.co.uk>
     * @since 1.0.0
     */
    public static function activate() {
        self::set_default_options();
    }

    /**
     * Delete any options set by this plugin.
     *
     * @return void
     * @author Keith Houston <keith@shadycharacters.co.uk>
     * @since 1.0.0
     */
    public static function uninstall() {
        self::delete_options();
    }

    /**
     * Called in response to the 'admin_menu' hook. Used to create a menu for this plugin.
     *
     * @return void
     * @author Keith Houston <keith@shadycharacters.co.uk>
     * @since 1.0.0
     */
    public function menu()
    {
        add_options_page(
            'Bibcite settings', // Browser page title
            'Bibcite', // Menu title
            'manage_options', // required capability
            self::MENU_SLUG, // menu slug
            array($this, 'do_options_page') // method to handle the menu
        );
    }

    /**
     * Called in response to the 'admin_init' hook. Used to register settings for this plugin.
     *
     * @return void
     * @author Keith Houston <keith@shadycharacters.co.uk>
     * @since 1.0.0
     */
    public function init()
    {
        // Register settings
        register_setting(self::SETTINGS_GROUP, self::LIBRARY_URL);
        register_setting(self::SETTINGS_GROUP, self::BIBSHOW_STYLE_NAME);
        register_setting(self::SETTINGS_GROUP, self::BIBSHOW_TEMPLATE_NAME);
        register_setting(self::SETTINGS_GROUP, self::BIBCITE_STYLE_NAME);
        register_setting(self::SETTINGS_GROUP, self::BIBCITE_TEMPLATE_NAME);
        register_setting(self::SETTINGS_GROUP, self::BIBTEX_STYLE_NAME);
        register_setting(self::SETTINGS_GROUP, self::BIBTEX_TEMPLATE_NAME);

        // Sections
        add_settings_section(
            self::LIBRARY_SECTION, // section ID
            'Default library', // section title
            array($this, 'do_library_section'), // section header callback
            self::MENU_SLUG // parent menu slug
        );

        add_settings_section(
            self::BIBSHOW_SECTION, // section ID
            'Notes', // section title
            array($this, 'do_bibshow_section'), // section header callback
            self::MENU_SLUG// parent menu slug
        );

        add_settings_section(
            self::BIBTEX_SECTION, // section ID
            'Bibliographies', // section title
            array($this, 'do_bibtex_section'), // section header callback
            self::MENU_SLUG// parent menu slug
        );

        // Fields
        add_settings_field(
            self::LIBRARY_URL, // field ID
            'Default library location', // field title
            array($this, 'do_library_url_chooser'), // field callback
            self::MENU_SLUG, // parent menu slug
            self::LIBRARY_SECTION // parent section ID
        );

        add_settings_field(
            self::BIBSHOW_STYLE_NAME, // field ID
            'Bibliography entry style', // field title
            array($this, 'do_bibshow_style_chooser'), // field callback
            self::MENU_SLUG, // parent menu slug
            self::BIBSHOW_SECTION// parent section ID
        );

        add_settings_field(
            self::BIBSHOW_TEMPLATE_NAME, // field ID
            'Bibliography list template', // field title
            array($this, 'do_bibshow_template_chooser'), // field callback
            self::MENU_SLUG, // parent menu slug
            self::BIBSHOW_SECTION// parent section ID
        );

        add_settings_field(
            self::BIBCITE_STYLE_NAME, // field ID
            'Note entry style', // field title
            array($this, 'do_bibcite_style_chooser'), // field callback
            self::MENU_SLUG, // parent menu slug
            self::BIBSHOW_SECTION// parent section ID
        );

        add_settings_field(
            self::BIBCITE_TEMPLATE_NAME, // field ID
            'Note list template', // field title
            array($this, 'do_bibcite_template_chooser'), // field callback
            self::MENU_SLUG, // parent menu slug
            self::BIBSHOW_SECTION// parent section ID
        );

        add_settings_field(
            self::BIBTEX_STYLE_NAME, // field ID
            'Bibliography entry style', // field title
            array($this, 'do_bibtex_style_chooser'), // field callback
            self::MENU_SLUG, // parent menu slug
            self::BIBTEX_SECTION// parent section ID
        );

        add_settings_field(
            self::BIBTEX_TEMPLATE_NAME, // field ID
            'Bibliography list template', // field title
            array($this, 'do_bibtex_template_chooser'), // field callback
            self::MENU_SLUG, // parent menu slug
            self::BIBTEX_SECTION// parent section ID
        );
    }

    /**
     * Callback used to populate our options page.
     *
     * @return void
     * @author Keith Houston <keith@shadycharacters.co.uk>
     * @since 1.0.0
     */
    public function do_options_page()
    {
        $redirect = urlencode( remove_query_arg( 'msg', $_SERVER['REQUEST_URI'] ) );

        ?>
    <div class="wrap">
        <h2>Bibcite settings</h2>
        <form action="options.php" method="POST">

            <?php 
            // Emit a of all known CSL styles
            $bibshow_style_names = \Bibcite\Common\CslRenderer::instance()->getCslStyleNames();
            echo "<datalist id='csl-style-names'>";
            foreach ($bibshow_style_names as $bibshow_style_name)
                echo "<option value='$bibshow_style_name'/>";
            echo "</datalist>";

            // Emit a list of all know Twig template files
            $bibshow_template_names = \Bibcite\Common\CslRenderer::instance()->getTwigTemplateNames();
            echo "<datalist id='twig-template-names'>";
            foreach ($bibshow_template_names as $bibshow_template_name)
                echo "<option value='$bibshow_template_name'/>";
            echo "</datalist>";
            ?>

            <?php settings_fields(self::SETTINGS_GROUP);?>
            <?php do_settings_sections(self::MENU_SLUG);?>
            <?php submit_button();?>
        </form>

        <form 
            action="<?php echo admin_url( 'admin-post.php' ); ?>" 
            method="post">
            <h2>Clear cached data</h2>
            Delete all cached files and database entries.
            <input 
                type="hidden" 
                name="action" 
                value="<?php echo esc_attr(self::CLEAR_CACHE_ACTION); ?>">
            <?php submit_button('Clear cache'); ?>
        </form>
    </div>
    <?php
    }
    
    /**
     * Callback used to populate our library.
     *
     * @return void
     * @author Keith Houston <keith@shadycharacters.co.uk>
     * @since 1.0.0
     */
    public function do_library_section()
    {
        echo "Choose the default library URL. The library at this location will be used for bibliographies whose shortcodes do not contain a <tt>file=[URL]</tt> attribute.";
    }

    /**
     * Callback used to populate our [bibshow] and [bibcite] section.
     *
     * @return void
     * @author Keith Houston <keith@shadycharacters.co.uk>
     * @since 1.0.0
     */
    public function do_bibshow_section()
    {
        echo "Manage styles and templates for notes and bibliographies generated by [bibshow] and [bibcite] shortcodes.";
    }

    /**
     * Callback used to populate our [bibtex] section.
     *
     * @return void
     * @author Keith Houston <keith@shadycharacters.co.uk>
     * @since 1.0.0
     */
    public function do_bibtex_section()
    {
        echo "Manage styles and templates for standalone bibliographies generated by [bibtex] shortcodes.";
    }

    /**
     * Callback used to populate the setting UI for our default library.
     *
     * @return void
     * @author Keith Houston <keith@shadycharacters.co.uk>
     * @since 1.0.0
     */
    public function do_library_url_chooser()
    {

        // Create the input control itself
        $id = self::LIBRARY_URL;
        $setting = esc_attr(get_option($id));        
        echo "<input type='text' name='$id' id='$id' value='$setting'/>";
        echo "<p class='description'>URL at which the default library file can be found.</p>";
    }

    /**
     * Callback used to populate the setting UI for our [bibshow] style.
     *
     * @return void
     * @author Keith Houston <keith@shadycharacters.co.uk>
     * @since 1.0.0
     */
    public function do_bibshow_style_chooser()
    {

        // Create the input control itself
        $id = self::BIBSHOW_STYLE_NAME;
        $setting = esc_attr(get_option($id));        
        echo "<input list='csl-style-names' name='$id' id='$id' value='$setting'/>";
        echo "<p class='description' title='Citation style language'><a href='http://citationstyles.org/'>CSL</a> style for individual bibliography entries</p>";
    }

    /**
     * Callback used to populate the setting UI for our [bibshow] template.
     *
     * @return void
     * @author Keith Houston <keith@shadycharacters.co.uk>
     * @since 1.0.0
     */
    public function do_bibshow_template_chooser()
    {

        // Create the input control itself
        $id = self::BIBSHOW_TEMPLATE_NAME;
        $setting = esc_attr(get_option($id));
        echo "<input list='twig-template-names' name='$id' id='$id' value='$setting'/>";
        echo "<p class='description'><a href='https://twig.symfony.com/'>Twig</a> template for a list of styled entries</p>";
    }

    /**
     * Callback used to populate the setting UI for our [bibcite] style.
     *
     * @return void
     * @author Keith Houston <keith@shadycharacters.co.uk>
     * @since 1.0.0
     */
    public function do_bibcite_style_chooser()
    {

        // Create the input control itself
        $id = self::BIBCITE_STYLE_NAME;
        $setting = esc_attr(get_option($id));
        echo "<input list='csl-style-names' name='$id' id='$id' value='$setting'/>";
        echo "<p class='description' title='Citation style language'><a href='http://citationstyles.org/'>CSL</a> style for individual note entries</p>";
    }

    /**
     * Callback used to populate the setting UI for our [bibcite] template.
     *
     * @return void
     * @author Keith Houston <keith@shadycharacters.co.uk>
     * @since 1.0.0
     */
    public function do_bibcite_template_chooser()
    {

        // Create the input control itself
        $id = self::BIBCITE_TEMPLATE_NAME;
        $setting = esc_attr(get_option($id));        
        echo "<input list='twig-template-names' name='$id' id='$id' value='$setting'/>";
        echo "<p class='description'><a href='https://twig.symfony.com/'>Twig</a> template for a list of styled entries rendered as a single note</p>";
    }

    /**
     * Callback used to populate the setting UI for our [bibtex] style.
     *
     * @return void
     * @author Keith Houston <keith@shadycharacters.co.uk>
     * @since 1.0.0
     */
    public function do_bibtex_style_chooser()
    {

        // Create the input control itself
        $id = self::BIBTEX_STYLE_NAME;
        $setting = esc_attr(get_option($id));        
        echo "<input list='csl-style-names' name='$id' id='$id' value='$setting'/>";
        echo "<p class='description' title='Citation style language'><a href='http://citationstyles.org/'>CSL</a> style for bibliography entries</p>";
    }

    /**
     * Callback used to populate the setting UI for our [bibtex] template.
     *
     * @return void
     * @author Keith Houston <keith@shadycharacters.co.uk>
     * @since 1.0.0
     */
    public function do_bibtex_template_chooser()
    {

        // Create the input control itself
        $id = self::BIBTEX_TEMPLATE_NAME;
        $setting = esc_attr(get_option($id));
        echo "<input list='bibtex-template-names' name='$id' id='$id' value='$setting'/>";
        echo "<p class='description'><a href='https://twig.symfony.com/'>Twig</a> template for bibliography list</p>";
    }

    /**
     * Delete all options set by this class.
     *
     * @return void
     * @author Keith Houston <keith@shadycharacters.co.uk>
     * @since 1.0.0
     */
    private static function delete_options() {
        remove_option(self::BIBSHOW_STYLE_NAME);
        remove_option(self::BIBSHOW_TEMPLATE_NAME);
        remove_option(self::BIBCITE_STYLE_NAME);
        remove_option(self::BIBCITE_TEMPLATE_NAME);
        remove_option(self::BIBTEX_STYLE_NAME);
        remove_option(self::BIBTEX_TEMPLATE_NAME);
    }

    /**
     * Set default values, if not already set.
     *
     * @return void
     * @author Keith Houston <keith@shadycharacters.co.uk>
     * @since 1.0.0
     */
    private static function set_default_options() {
        add_option(self::BIBSHOW_STYLE_NAME, "ieee");
        add_option(self::BIBSHOW_TEMPLATE_NAME, "bibshow-definition-list");
        add_option(self::BIBCITE_STYLE_NAME, "ieee");
        add_option(self::BIBCITE_TEMPLATE_NAME, "bibcite-numbered-note");
        add_option(self::BIBTEX_STYLE_NAME, "ieee");
        add_option(self::BIBTEX_TEMPLATE_NAME, "bibtex-unordered-list");        
    }
}
<?php

namespace Bibcite\Common;

require plugin_dir_path(dirname(__FILE__)) . 'vendor\autoload.php';

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin, the current version of 
 * the plugin, and other plugin-wide constants.
 * 
 * @author Keith Houston <keith@shadycharacters.co.uk>
 * @link https://github.com/OrkneyDullard/Bibcite
 * @package Bibcite\Common
 * @since 1.0.0
 */
class Bibcite
{

    /**
     * The loader that's responsible for maintaining and registering all hooks 
     * that power the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Bibcite_Loader    $loader    Maintains and registers all hooks 
     * for the plugin.
     */
    protected $loader;

    /**
     * The unique identifier of this plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $bibcite    The string used to uniquely identify this 
     * plugin.
     */
    protected $bibcite;

    /**
     * The current version of the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $version    The current version of the plugin.
     */
    protected $version;

    /**
     * Define the core functionality of the plugin.
     *
     * Set the plugin name and the plugin version that can be used throughout 
     * the plugin. Load the dependencies, define the locale, and set the hooks 
     * for the admin area and the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function __construct()
    {

        Logger::instance()->debug("Loading Bibcite plugin...");

        if (defined('BIBCITE_VERSION')) {
            $this->version = BIBCITE_VERSION;
        } else {
            $this->version = '1.0.0';
        }
        $this->bibcite = 'bibcite';

        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
        $this->define_shortcodes();

    }

    /**
     * Load the required dependencies for this plugin.
     *
     * Include the following files that make up the plugin:
     *
     * - Bibcite_Loader. Orchestrates the hooks of the plugin.
     * - Bibcite_i18n. Defines internationalization functionality.
     * - Bibcite_Admin. Defines all hooks for the admin area.
     * - Bibcite_Public. Defines all hooks for the public side of the site.
     *
     * Create an instance of the loader which will be used to register the hooks
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function load_dependencies()
    {

        $this->loader = new Loader();

    }

    /**
     * Define the locale for this plugin for internationalization.
     *
     * Uses the I18n class in order to set the domain and to register the hook
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function set_locale()
    {

        $plugin_i18n = new I18n();

        $this->loader->add_action(
            'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain'
        );

    }

    /**
     * Register all of the hooks related to the admin area functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_admin_hooks()
    {

        $plugin_admin = new \Bibcite\Admin\Admin(
            $this->get_bibcite(), $this->get_version()
        );

        $this->loader->add_action('admin_init', $plugin_admin, 'init');
        $this->loader->add_action('admin_menu', $plugin_admin, 'menu');

    }

    /**
     * Register all of the hooks related to the public-facing functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_public_hooks()
    {

        // Nothing to do

    }

    /**
     * Register all of the shortcodes supported by the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_shortcodes()
    {

        $plugin_public = new \Bibcite\Main\Main(
            $this->get_bibcite(), $this->get_version()
        );

        $this->loader->add_shortcode(
            'bibcite', $plugin_public, 'do_bibcite_shortcode'
        );
        $this->loader->add_shortcode(
            'bibshow', $plugin_public, 'do_bibshow_shortcode'
        );
        $this->loader->add_shortcode(
            'bibtex', $plugin_public, 'do_bibtex_shortcode'
        );

    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     *
     * @since    1.0.0
     */
    public function run()
    {
        $this->loader->run();
    }

    /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.
     *
     * @since     1.0.0
     * @return    string    The name of the plugin.
     */
    public function get_bibcite()
    {
        return $this->bibcite;
    }

    /**
     * The reference to the class that orchestrates the hooks with the plugin.
     *
     * @since     1.0.0
     * @return    Bibcite_Loader    Orchestrates the hooks of the plugin.
     */
    public function get_loader()
    {
        return $this->loader;
    }

    /**
     * Retrieve the version number of the plugin.
     *
     * @since     1.0.0
     * @return    string    The version number of the plugin.
     */
    public function get_version()
    {
        return $this->version;
    }
}

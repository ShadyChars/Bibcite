<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin admin area. This
 * file also includes all of the dependencies used by the plugin, registers activation,
 * deactivation and uninstallation functions, and defines a function that starts the plugin.
 *
 * @link              https://github.com/ShadyChars/Bibcite
 * @since             1.0.0
 * @package           Bibcite
 *
 * @wordpress-plugin
 * Plugin Name:       Bibcite-CSL
 * Plugin URI:        https://github.com/ShadyChars/Bibcite
 * Description:       A simple CSL JSON parser and citation generator for WordPress.
 * Version:           2.0.0
 * Author:            Keith Houston
 * Author URI:        https://github.com/ShadyChars/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       bibcite
 * Domain Path:       /languages
 */

if (!defined('WPINC')) {
    die;
}

require plugin_dir_path(__FILE__) . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

/**
 * Current plugin version.
 */
define('BIBCITE_VERSION', '2.0.0');

/**
 * A general prefix used to scope databases, tables, constants, and so on.
 */
define('BIBCITE_PREFIX', 'BIBCITE');

/**
 * Directory used to cache downloaded files and compiles templates, relative to 
 * the main plugin directory.
 */
define('BIBCITE_CACHE_DIRECTORY', 'cache');

/**
 * Define a custom hook fired when the plugin is reset to a factory state.
 */
define('BIBCITE_CLEAR_CACHE_ACTION', BIBCITE_PREFIX . "_CLEAR_CACHE");

/**
 * The code that runs during plugin activation.
 */
function activate_bibcite()
{
    Bibcite\Common\Logger::instance()->info("Activating plugin...");
    Bibcite\Common\Activator::activate();
    Bibcite\Common\Logger::instance()->info("Activated plugin.");
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_bibcite()
{
    Bibcite\Common\Logger::instance()->info("Deactivating plugin...");
    Bibcite\Common\Deactivator::deactivate();
    Bibcite\Common\Logger::instance()->info("Deactivated plugin.");
}

/**
 * The code that runs during our custom BIBCITE_CLEAR_CACHE_ACTION.
 */
function clear_cache_bibcite()
{
    Bibcite\Common\Logger::instance()->info("Clearing cached data...");
    Bibcite\Common\CacheClearer::clear_cache();
    Bibcite\Common\Logger::instance()->info("Cleared cached data.");
}

/**
 * The code that runs during plugin uninstallation.
 */
function uninstall_bibcite()
{
    Bibcite\Common\Logger::instance()->info("Uninstalling plugin...");
    Bibcite\Common\Uninstaller::uninstall();
    Bibcite\Common\Logger::instance()->info("Uninstalled plugin.");
}

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_bibcite()
{
    register_activation_hook(__FILE__, 'activate_bibcite');
    register_deactivation_hook(__FILE__, 'deactivate_bibcite');
    register_uninstall_hook(__FILE__, 'uninstall_bibcite');
    add_action(BIBCITE_CLEAR_CACHE_ACTION, 'clear_cache_bibcite');

    $plugin = new Bibcite\Common\Bibcite();
    $plugin->run();

}
run_bibcite();

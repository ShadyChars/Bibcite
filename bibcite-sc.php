<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin admin area. This
 * file also includes all of the dependencies used by the plugin, registers activation,
 * deactivation and uninstallation functions, and defines a function that starts the plugin.
 *
 * @link              https://github.com/OrkneyDullard/bibcite
 * @since             1.0.0
 * @package           Bibcite
 *
 * @wordpress-plugin
 * Plugin Name:       bibcite-sc
 * Plugin URI:        https://github.com/OrkneyDullard/bibcite
 * Description:       A simple Bibtex parser and citation generator.
 * Version:           1.0.0
 * Author:            Keith Houston
 * Author URI:        https://github.com/OrkneyDullard/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       bibcite-sc
 * Domain Path:       /languages
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

require plugin_dir_path(__FILE__) . 'vendor\autoload.php';

/**
 * Currently plugin version.
 */
define( 'BIBCITE_SC_VERSION', '1.0.0' );

/**
 * A general prefix used to scope databases, tables, constants, and so on.
 */
define( 'BIBCITE_SC_PREFIX', 'BIBCITE_SC' );

/**
 * Directory used to cache downloaded files and compiles templates, relative to the main plugin
 * directory.
 */
define( 'BIBCITE_SC_CACHE_DIRECTORY', 'cache' );

/**
 * Define a custom hook fired when the plugin is reset to a factory state.
 */
define( 'BIBCITE_SC_CLEAR_CACHE_ACTION', BIBCITE_SC_PREFIX . "_CLEAR_CACHE" );

/**
 * The code that runs during plugin activation.
 */
function activate_bibcite_sc() {
	Bibcite\Common\Logger::instance()->info("Activating plugin...");
	Bibcite\Common\Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_bibcite_sc() {
	Bibcite\Common\Logger::instance()->info("Dectivating plugin...");
	Bibcite\Common\Deactivator::deactivate();
}

/**
 * The code that runs during our custom BIBCITE_SC_CLEAR_CACHE_ACTION.
 */
function clear_cache_bibcite_sc() {
	Bibcite\Common\Logger::instance()->info("Clearing cached data...");
	Bibcite\Common\CacheClearer::clear_cache();
}

/**
 * The code that runs during plugin uninstallation.
 */
function uninstall_bibcite_sc() {
	Bibcite\Common\Logger::instance()->info("Uninstalling plugin...");
	Bibcite\Common\Uninstaller::uninstall();
}

register_activation_hook( __FILE__, 'activate_bibcite_sc' );
register_deactivation_hook( __FILE__, 'deactivate_bibcite_sc' );
register_uninstall_hook( __FILE__, 'uninstall_bibcite_sc' );
add_action(BIBCITE_SC_CLEAR_CACHE_ACTION, 'clear_cache_bibcite_sc');

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_bibcite_sc() {

	$plugin = new Bibcite\Common\Bibcite();
	$plugin->run();

}
run_bibcite_sc();

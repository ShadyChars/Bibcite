<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin admin area. This
 * file also includes all of the dependencies used by the plugin, registers activation,
 * deactivation and uninstallation functions, and defines a function that starts the plugin.
 *
 * @link              http://example.com
 * @since             1.0.0
 * @package           Bibcite_SC
 *
 * @wordpress-plugin
 * Plugin Name:       bibcite-sc
 * Plugin URI:        http://example.com/bibcite-sc-uri/
 * Description:       A simple Bibtex parser and citation generator.
 * Version:           1.0.0
 * Author:            Keith Houston
 * Author URI:        http://example.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       bibcite-sc
 * Domain Path:       /languages
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

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
 * The code that runs during plugin activation.
 * This action is documented in includes/class-bibcite-sc-activator.php
 */
function activate_bibcite_sc() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-bibcite-sc-activator.php';
	Bibcite_SC_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-bibcite-sc-deactivator.php
 */
function deactivate_bibcite_sc() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-bibcite-sc-deactivator.php';
	Bibcite_SC_Deactivator::deactivate();
}

/**
 * The code that runs during plugin uninstallation.
 * This action is documented in includes/class-bibcite-sc-uninstaller.php
 */
function uninstall_bibcite_sc() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-bibcite-sc-uninstaller.php';
	Bibcite_SC_uninstaller::uninstall();
}

register_activation_hook( __FILE__, 'activate_bibcite_sc' );
register_deactivation_hook( __FILE__, 'deactivate_bibcite_sc' );
register_uninstall_hook( __FILE__, 'uninstall_bibcite_sc' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-bibcite-sc.php';

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

	$plugin = new Bibcite_SC();
	$plugin->run();

}
run_bibcite_sc();

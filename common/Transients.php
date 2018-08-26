<?php

namespace Bibcite\Common;

require plugin_dir_path( dirname( __FILE__ ) ) . 'vendor/autoload.php';

/**
 * A singleton class for managing transients.
 * 
 * Transients created via this class are automatically prefixed. They may be
 * cleared or uninstalled as required.
 *
 * @author Keith Houston <keith@shadycharacters.co.uk>
 * @link https://github.com/ShadyChars/Bibcite
 * @package Bibcite\Common
 * @since 1.0.0
 */
class Transients
{
	// Name of the option we use to record our transient names.
	private const OPTION_NAME = 
		BIBCITE_PREFIX . "_" . __CLASS__ . "_TRANSIENT_NAMES";

	// Hold an instance of the class
	private static $instance;

	/**
	 * Get the singleton instance of this class.
	 *
	 * @return Transients
	 * @author Keith Houston <keith@shadycharacters.co.uk>
	 * @since 1.0.0
	 */
	public static function instance() : Transients {
		if (!isset(self::$instance)) {
			self::$instance = new Transients();
		}
		return self::$instance;
	}

	/**
	 * Set or update a transient.
	 *
	 * @param string $name name of the transient. This will be automatically
	 * prefixed.
	 * @param mixed $value value of the transient
	 * @param int $expires lifetime in seconds
	 * @return void
	 * @author Keith Houston <keith@shadycharacters.co.uk>
	 * @since 1.0.0
	 */
	public function set_transient(string $name, $value, int $expires) {

		// Compute and record the prefixed transient name so we can delete
		// it later.
		$prefixed_transient_name = self::get_transient_name($name);
		self::add_transient_name($prefixed_transient_name);
		
		// Add or update the transient.
		set_transient($prefixed_transient_name, $value, $expires);
	}

	/**
	 * Get a transient value or a default.
	 *
	 * @param string $name name of the transient. This will be automatically
	 * prefixed.
	 * @return mixed|false the transient value, the default value, or false
	 * @author Keith Houston <keith@shadycharacters.co.uk>
	 * @since 1.0.0
	 */
	public function get_transient(string $name) {
		$prefixed_transient_name = self::get_transient_name($name);
		return get_transient($prefixed_transient_name);
	}

	/**
     * Clear out cached data.
     *
     * @return void
     * @author Keith Houston <keith@shadycharacters.co.uk>
     * @since 1.0.0
     */
    public static function clear_cache() {
        self::uninstall();
    }

    /**
     * Clear out cached data.
     *
     * @return void
     * @author Keith Houston <keith@shadycharacters.co.uk>
     * @since 1.0.0
     */
    public static function uninstall() {

		$logger = new ScopedLogger(Logger::instance(), __METHOD__ ." - ");

		// Get all known transient names
		$transient_names = get_option(self::OPTION_NAME, array());

		foreach ($transient_names as $key => $transient_name) {
			$logger->debug("Deleting transient: $transient_name...");
			delete_transient($transient_name);
		}

		// Clear our recorded list of transients.
		delete_option(self::OPTION_NAME);
	}

	/**
	 * Compute a predictable, prefixed name for the specified transient.
	 *
	 * @param string $name base transient name
	 * @return string prefixed transient name
	 * @author Keith Houston <keith@shadycharacters.co.uk>
	 * @since 1.0.0
	 */
	private static function get_transient_name(string $name) : string {
		return BIBCITE_PREFIX . "_$name";
	}

	/**
	 * Note that a transient with the given name has been used.
	 *
	 * @param string $prefixed_transient_name prefixed transient name
	 * @return void
	 * @author Keith Houston <keith@shadycharacters.co.uk>
	 * @since 1.0.0
	 */
	private static function add_transient_name(
		string $prefixed_transient_name
	) {
		$transient_names = get_option(self::OPTION_NAME, array());

		if (!in_array($prefixed_transient_name, $transient_names)) {
			$logger = new ScopedLogger(Logger::instance(), __METHOD__ ." - ");
			$logger->debug(
				"Recording new transient: $prefixed_transient_name..."
			);
			$transient_names[] = $prefixed_transient_name;
		}

		update_option(self::OPTION_NAME, $transient_names);
	}
}
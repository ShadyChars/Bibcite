<?php

require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes\class-bibcite-logger.php';
require plugin_dir_path( dirname( __FILE__ ) ) . 'vendor\autoload.php';

/**
 * Provides a single point of access for persistent options/settings.
 */

// TODO: how to reconcile this class w/ Bibcite_SC_Admin?
class Bibcite_Options
{
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
	 * Default values for options. 
	 */
	private const DEFAULT_VALUES = array(
		Bibcite_Options::LIBRARY_URLS => array(),
		Bibcite_Options::BIBCITE_STYLE_NAME => "ieee",
		Bibcite_Options::BIBSHOW_STYLE_NAME => "ieee",
		Bibcite_Options::BIBTEX_STYLE_NAME => "ieee",
		Bibcite_Options::BIBCITE_TEMPLATE_NAME => "bibcite_numeric_footnote",
		Bibcite_Options::BIBSHOW_TEMPLATE_NAME => "bibshow_definition_list",
		Bibcite_Options::BIBTEX_STYLE_NAME => "bibshow_unordered_list"
	);

	/**
	 * Get the singleton instance of this class.
	 *
	 * @return Bibcite_Logger
	 * @author Keith Houston <keith@shadycharacters.co.uk>
	 * @since 1.0.0
	 */
	private static $instance;

	// The singleton method
	public static function instance() : Bibcite_Options {
		if (!isset(self::$instance)) {
			self::$instance = new Bibcite_Options();
		}
		return self::$instance;
	}

	/**
	 * Reset all options to factory status.
	 *
	 * @return void
	 */
	public function reset() {
		foreach (Bibcite_Option::DEFAULT_VALUES as $name => $value)
			update_option($name, $value);
	}

	/**
	 * Delete all options.
	 *
	 * @return void
	 */
	public function uninstall() {
		foreach (Bibcite_Option::DEFAULT_VALUES as $name => $value)
			delete_option($name);
	}

	/**
	 * Set the named option to the specfied value.
	 *
	 * @param string $option_name option name
	 * @param string $option_value option value
	 * @return void
	 */
	public function setOption(string $option_name, string $option_value) {
		if (!array_key_exists($option_name, Bibcite_Option::DEFAULT_VALUES)) {
			Bibcite_Logger::instance()->error("Unrecognised option '$option_name' will not be set");
			return;
		}

		update_option($option_name, $option_value);
	}

	/**
	 * Get the value of the named option.
	 *
	 * @param string $option_name option name
	 * @return string option value
	 */
	public function getOption(string $option_name) : ?string {
		if (!array_key_exists($option_name, Bibcite_Option::DEFAULT_VALUES)) {
			Bibcite_Logger::instance()->error(
				"Unrecognised option '$option_name' cannot be retrieved"
			);
			return null;
		}

		return get_option($option_name, Bibcite_Options::DEFAULT_VALUES[$option_name]);
	}
}
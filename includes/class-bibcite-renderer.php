<?php

require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes\class-bibcite-logger.php';
require plugin_dir_path( dirname( __FILE__ ) ) . 'vendor\autoload.php';

/**
 * Provides a single point of access to citation rendering services and associated data.
 *
 * @author Keith Houston <keith@shadycharacters.co.uk>
 * @since 1.0.0
 */
class Bibcite_Renderer
{
	// Hold an instance of the class
	private static $instance;

	// The set of known style names.
	private $csl_style_names;

	/**
	 * Get the singleton instance of this class.
	 *
	 * @return Bibcite_Renderer
	 * @author Keith Houston <keith@shadycharacters.co.uk>
	 * @since 1.0.0
	 */
	public static function instance() : Bibcite_Renderer
	{
		if (!isset(self::$instance)) {
			self::$instance = new Bibcite_Renderer();
		}
		return self::$instance;
	}

	private function __construct() {

		$csl_styles_path = implode( 
			DIRECTORY_SEPARATOR, 
			array( 
				plugin_dir_path(dirname(__FILE__)), 
				'vendor', 
				'citation-style-language',
				'styles-distribution'
			)
		);

		$dir_iterator = new RecursiveDirectoryIterator($csl_styles_path);
		$iterator = new RecursiveIteratorIterator(
			$dir_iterator, RecursiveIteratorIterator::SELF_FIRST
		);

		$this->csl_style_names = array();
		foreach ($iterator as $csl_style_file) 
			$this->csl_style_names[] = basename(substr($csl_style_file, 0, -4));
	}

	/**
	 * Get the list of all supported CSL styles.
	 *
	 * @return array list of all supported CSL styles
	 * @author Keith Houston <keith@shadycharacters.co.uk>
	 * @since 1.0.0
	 */
	public function getCslStyleNames() : array {
		return $this->csl_style_names;
	}

	/**
	 * Render a CSL entry presented as an associative array using a named CSL style.
	 *
	 * @param array $csl_array CSL entry to be rendered
	 * @param string $csl_style_name named CSL style with which to render
	 * @return string rendered entry
	 * @author Keith Houston <keith@shadycharacters.co.uk>
	 * @since 1.0.0
	 */
	public function render(array $csl_array, string $csl_style_name) : string {

		// TK - modify this to accept Bibtex strings. Or should CSL become the default data storage
		// format for the plugin?
	}
}
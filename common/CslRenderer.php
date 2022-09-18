<?php

namespace Bibcite\Common;

require plugin_dir_path(dirname(__FILE__)) . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

/**
 * Provides a single point of access to citation rendering services and 
 * associated data.
 *
 * @author Keith Houston <keith@shadycharacters.co.uk>
 * @link https://github.com/ShadyChars/Bibcite
 * @package Bibcite\Common
 * @since 1.0.0
 */
class CslRenderer
{
	// Hold an instance of the class
	private static $instance;

	// The location of known CSL style files.
	private $csl_styles_path;

	// The location of user-specified CSL style files.
	private $user_csl_styles_path;

	// The location of known Twig template files.
	private $twig_templates_path;

	// The set of known CSL style names.
	private $csl_style_names;

	// The set of known user-specified CSL style names.
	private $user_csl_style_names;

	// The set of known Twig template names.
	private $twig_template_names;

	/**
	 * Get the singleton instance of this class.
	 *
	 * @return CslRenderer
	 * @author Keith Houston <keith@shadycharacters.co.uk>
	 * @since 1.0.0
	 */
	public static function instance() : CslRenderer
	{
		if (!isset(self::$instance)) {
			self::$instance = new CslRenderer();
		}
		return self::$instance;
	}

	private function __construct() {

		// List CSL files
		$this->csl_styles_path = implode( 
			DIRECTORY_SEPARATOR, 
			array( 
				untrailingslashit(plugin_dir_path(dirname(__FILE__))), 
				'vendor', 
				'citation-style-language',
				'styles-distribution'
			)
		);

		$dir_iterator = new \RecursiveDirectoryIterator($this->csl_styles_path);
		$iterator = new \RecursiveIteratorIterator(
			$dir_iterator, \RecursiveIteratorIterator::SELF_FIRST
		);

		$this->csl_style_names = array();
		foreach ($iterator as $csl_style_file) 
			if (substr($csl_style_file, -4) == ".csl")
				$this->csl_style_names[] = 
					basename(substr($csl_style_file, 0, -4));

		// List user-generated styles
		$this->user_csl_styles_path = plugin_dir_path(dirname(__FILE__)) . 'styles';

		$dir_iterator = 
			new \RecursiveDirectoryIterator($this->user_csl_styles_path);
		$iterator = new \RecursiveIteratorIterator(
			$dir_iterator, \RecursiveIteratorIterator::SELF_FIRST
		);

		foreach ($iterator as $user_csl_style_file) 
			if (substr($user_csl_style_file, -4) == ".csl")
				$this->user_csl_style_names[] = 
					basename(substr($user_csl_style_file, 0, -4));

		// List Twig templates
		$this->twig_templates_path = plugin_dir_path(dirname(__FILE__)) .'templates';

		$dir_iterator = 
			new \RecursiveDirectoryIterator($this->twig_templates_path);
		$iterator = new \RecursiveIteratorIterator(
			$dir_iterator, \RecursiveIteratorIterator::SELF_FIRST
		);

		$this->twig_template_names = array();
		foreach ($iterator as $twig_template_filename) 
			if (substr($twig_template_filename, -5) == ".twig")
				$this->twig_template_names[] = 
					basename(substr($twig_template_filename, 0, -5));
	}

	/**
	 * Get the list of all supported CSL styles, including user-generated 
	 * styles.
	 *
	 * @return array list of all supported CSL styles
	 * @author Keith Houston <keith@shadycharacters.co.uk>
	 * @since 1.0.0
	 */
	public function getCslStyleNames() : array {
		return array_merge($this->csl_style_names, $this->user_csl_style_names);
	}

	/**
	 * Get the list of all supported Twig templates.
	 *
	 * @return array list of all known Twig templates
	 * @author Keith Houston <keith@shadycharacters.co.uk>
	 * @since 1.0.0
	 */
	public function getTwigTemplateNames() : array {
		return $this->twig_template_names;
	}

	/**
	 * Render an ordered collection of CSL entries (each one presented as a CSL 
	 * JSON object) using a named CSL entry style and a Twig list.
	 *
	 * @param array $csl_entries ordered list of CSL entries (each one an a CSL 
	 * JSON object) to be rendered. The numeric index of each entry is used as 
	 * the index of the rendered item when passed to the template engine.
	 * @param string $csl_style_name named CSL style with which to render each 
	 * entry
	 * @param string $twig_template_name named Twig template file with which to 
	 * render the list
	 * @return string rendered list
	 * @author Keith Houston <keith@shadycharacters.co.uk>
	 * @since 1.0.0
	 */
	public function renderCslEntries(
		array $csl_entries, 
		string $csl_style_name, 
		string $twig_template_name
	) : string {

		$logger = new ScopedLogger(Logger::instance(), __METHOD__ ." - ");

		// Load the style. Note that this may be a user-specified style.
		$style = null;
		try
		{
			if (in_array($csl_style_name, $this->user_csl_style_names)) {			
				$user_style_file = implode( 
					DIRECTORY_SEPARATOR, 
					array(untrailingslashit($this->user_csl_styles_path), $csl_style_name . '.csl')
				);
				$logger->debug("Using custom style: $user_style_file");
				$style = file_get_contents($user_style_file);
			} else if (in_array($csl_style_name, $this->csl_style_names)) {
				$logger->debug("Using built-in style: $csl_style_name");
				$style = \Seboettg\CiteProc\StyleSheet::loadStyleSheet(
					$csl_style_name
				);
			} else {
				$csl_default_style = $this->csl_style_names[0];
				$logger->warning(
					"Unrecognised style: $csl_style_name. Defaulting to $csl_default_style."
				);
				$style = \Seboettg\CiteProc\StyleSheet::loadStyleSheet(
					$csl_default_style
				);
			}
		} catch (\Exception $e) {
			$logger->error(
				"Exception when determining CSL style: " . $e->getMessage()
			);
			return "";
		}

		// Render the entries with the specified CiteProc style
		try	{
			$citeProc = new \Seboettg\CiteProc\CiteProc($style);
		} catch (\Exception $e) {
			$logger->error(
				"Exception when loading CSL style: " . $e->getMessage()
			);
			return "";
		}
		
		$rendered_entries = array();
		foreach ($csl_entries as $index => $csl_entry) {
			try {
				$key = "unknown_key";
				$rendered_entry = 
					"<span style='color:gray'>Unknown entry</span>";
	
				// Render the citation. CiteProc expects an array of CSL JSON 
				// objects, but we're rendering each one individually.
				try {
					// TODO: modify this so that we can emit the failed key.
					if (!empty($csl_entry)) {
						$key = $csl_entry->{'citation-label'};
						$rendered_entry = $citeProc->render(array($csl_entry), "citation");
						$logger->debug(json_encode($csl_entry));
					}				
				} catch (\Error $error) {
					$logger->error(
						"Error when rendering CSL: " . 
						$error->getMessage()
					);
					$rendered_entry = 
						"<span style='color:OrangeRed'>Error when rendering entry ($key)</span>";
				} catch (\Exception $exception) {
					$logger->error(
						"Exception when rendering CSL: " . 
						$exception->getMessage()
					);
					$rendered_entry = 
						"<span style='color:Orange'>Exception when rendering entry ($key)</span>";
				}

				// Save the rendered entry as part of an array.
				$rendered_entries[] = array(
					"index" => $index,					// integer index
					"key" => $key,						// citation key string
					"csl" => json_encode($csl_entry),	// CSL JSON as string
					"entry" => $rendered_entry			// rendered citation
				);
			} catch (\Error $error) {
				$logger->error(
					"Error when constructing entry for template: " . 
					$error->getMessage()
				);
			} catch (\Exception $exception) {
				$logger->error(
					"Exception when constructing entry for template: " . 
					$exception->getMessage()
				);
			}
		}

		// Select the template style. Note that this may be our single hard-
		// coded template.
		$loader = null;
		try
		{
			$user_template_file = implode( 
				DIRECTORY_SEPARATOR, 
				array(untrailingslashit($this->twig_templates_path), $twig_template_name . '.twig')
			);
			if (in_array($twig_template_name, $this->twig_template_names)) {			
				$logger->debug("Using custom template: $user_template_file");
				$loader = new \Twig_Loader_Filesystem($this->twig_templates_path);
			} else {
				$logger->warning(
					"Unrecognised template: $user_template_file. Defaulting to built-in unordered list."
				);
				$twig_template_name = "built-in-unordered-list";
				$loader = new \Twig_Loader_Array(
					array(
						"${twig_template_name}.twig" => <<<TWIG_DEFAULT_TEMPLATE
<ul class="bibcite-default-template">
{% for entry in entries %}
	<li>{{ entry.entry | raw }}</li>
{% endfor %}
</ul>
TWIG_DEFAULT_TEMPLATE
					)
				);
			}
		} catch (\Exception $e) {
			$logger->error(
				"Exception when determing Twig template: " . $e->getMessage()
			);
			return "";
		}

		// Now apply the list template.
		try {			
			$twig_cache_dir = implode( 
				DIRECTORY_SEPARATOR, 
				array( 
					untrailingslashit(plugin_dir_path(dirname(__FILE__))), 
					BIBCITE_CACHE_DIRECTORY, 
					'twig'
				)
			);
			$twig = new \Twig_Environment(
				$loader, array('cache' => $twig_cache_dir)
			);
			return $twig->render(
				"${twig_template_name}.twig", 
				array('entries' => $rendered_entries)
			);
		} catch (\Exception $e) {
			$logger->error(
				"Exception when rendering CSL with template: " . $e->getMessage()
			);
			return "";
		}
	}
}
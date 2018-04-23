<?php

namespace Bibcite\Main;

require plugin_dir_path( dirname( __FILE__ ) ) . 'vendor\autoload.php';

use Geissler\Converter\Converter;
use Geissler\Converter\Standard\BibTeX\BibTeX;
use Geissler\Converter\Standard\CSL\CSL;

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://github.com/OrkneyDullard/bibcite
 * @since      1.0.0
 *
 * @package    Bibcite\Main
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Bibcite\Main
 * @author     Keith Houston <keith@shadycharacters.co.uk>
 */
class Main {

	// Names of parsed shortcode attributes returned by parse_shortcode_attributes().
	private const URL_ATTRIBUTE = BIBCITE_SC_PREFIX . "_URL";
	private const KEYS_ATTRIBUTE = BIBCITE_SC_PREFIX . "_KEYS";
	private const STYLE_ATTRIBUTE = BIBCITE_SC_PREFIX . "_STYLE";
	private const TEMPLATE_ATTRIBUTE = BIBCITE_SC_PREFIX . "_TEMPLATE";
	private const SORT_ATTRIBUTE = BIBCITE_SC_PREFIX . "_SORT";
	private const ORDER_ATTRIBUTE = BIBCITE_SC_PREFIX . "_ORDER";

	// The ID of this plugin.
	private $bibcite_sc;

	// The version of this plugin.
	private $version;

	// A [post ID -> [key]] array of arrays. Lists the set of unique keys per post.
	private $post_id_to_bibshow_keys;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $bibcite_sc       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $bibcite_sc, $version ) {

		$this->bibcite_sc = $bibcite_sc;
		$this->version = $version;
		$this->post_id_to_bibshow_keys = array();
	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Bibcite_SC_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Bibcite_SC_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->bibcite_sc, plugin_dir_url( __FILE__ ) . 'css/bibcite-sc-public.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Bibcite_SC_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Bibcite_SC_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->bibcite_sc, plugin_dir_url( __FILE__ ) . 'js/bibcite-sc-public.js', array( 'jquery' ), $this->version, false );

	}

	/**
	 * Handle a standalone [bibtex] shortcode. This renders a bibliography at the shortcode 
	 * location, populated only with the specified references.
	 *
	 * @param array $atts shortcode attributes
	 * @param string $content shortcode content. Ignored.
	 * @return string|null
	 */
	public function do_bibtex_shortcode( array $atts, string $content = null ) {

		\Bibcite\Common\Logger::instance()->debug(
			"Encountered bibtex shortcode with attributes: " . var_export($atts, true)
		);

		// Parse attributes
		$attributes = self::parse_shortcode_attributes($atts);

		// Get or update the library for the source URL
		$csl_library = self::get_or_update_csl_library($attributes[self::URL_ATTRIBUTE]);

		// Get the set of CSL JSON objects to be rendered.
		$csl_entries = array();
		foreach (explode(",", $attributes[self::KEYS_ATTRIBUTE]) as $csl_key) {
			$csl_json_object = $csl_library->get($csl_key);
			if ($csl_json_object) {
				\Bibcite\Common\Logger::instance()->debug("Found CSL library entry: " . $csl_key);
				$csl_entries[] = $csl_json_object;
			}
			else
				\Bibcite\Common\Logger::instance()->warn(
					"Could not find CSL library entry: " . $csl_key
				);
		}

		// Do we need to sort the entries?
		if ($attributes[self::SORT_ATTRIBUTE]) {
			$sort = $attributes[self::SORT_ATTRIBUTE];
			$order = $attributes[self::ORDER_ATTRIBUTE];

			// Run a string comparison on the specified field. Invert the comparison score if we 
			// want the sort order to be descending.
			usort(
				$csl_entries,
				function($a, $b) use ($sort, $order) {
					try {						
						$cmp = strcmp(var_export($a->{$sort}, true), var_export($b->{$sort}, true));
						return ($order == "asc") ? $cmp : -$cmp;
					}
					catch (Exception $e) {
						\Bibcite\Common\Logger::instance()->warn(
							"Could not sort on CSL attribute '$sort': " . $e->getMessage()
						);
						return 0;
					}
				}
			);
		}

		// Render and return the bibliography.
		return \Bibcite\Common\CslRenderer::instance()->renderCslEntries(
			$csl_entries, $attributes[self::STYLE_ATTRIBUTE], $attributes[self::TEMPLATE_ATTRIBUTE]
		);
	}

	/**
	  * Handle a [bibcite] shortcode. When used inside a [bibshow]...[/bibshow] shortcode, this 
	 * inserts a citation or a note to a specified citation or citations.
	 *
	 * @param array $atts shortcode attributes. Only "key=<key1>[,<key2>[,...]]" is supported.
	 * @param string $content shortcode content. Ignored.
	 * @return string the string with which the [bibcite] shortcode it to be replaced.
	 * @author Keith Houston <keith@shadycharacters.co.uk>
	 * @since 1.0.0	
	 * */
	public function do_bibcite_shortcode( array $atts, string $content = null ) : string {

		\Bibcite\Common\Logger::instance()->debug(
			"Encountered bibcite shortcode with attributes: " . var_export($atts, true)
		);

		// Work out what post we're in.
		global $post;
		$post_id = $post->ID;
		
		// Do we already have an array of known keys for this post? If not, create one.
		if (!isset($this->post_id_to_bibshow_keys[$post_id])) {
			$this->post_id_to_bibshow_keys[$post_id] = array();
			\Bibcite\Common\Logger::instance()->debug(
				"Creating new array of [key => rendered entry] for post ${post_id}"
			);
		}

		// Parse attributes
		$attributes = self::parse_shortcode_attributes($atts);

		// $keys_for_post contains only unique keys within this post in the order that we encounter
		// them. Separately, build a list of keys *for this shortcode only* to be rendered and 
		// emitted at the end of this method.
		$keys_for_post = &$this->post_id_to_bibshow_keys[$post_id];
		$keys_for_shortcode = explode(",", $attributes[self::KEYS_ATTRIBUTE]);

		// Do we need to do anything?
		if (count($keys_for_shortcode) <= 0)
			return "";

		// If we're here, we need to render our notes. First, get the library.
		$url = $attributes[self::URL_ATTRIBUTE];
		$csl_library = self::get_or_update_csl_library($url);

		// For each key in this shortcode...		
		$indexed_csl_values_to_render = array();
		foreach ($keys_for_shortcode as $csl_key) {

			// If this key doesn't already exist in the list of keys to be rendered in the 
			// bibliography, add it now.
			if (!in_array($csl_key, $keys_for_post))
				$keys_for_post[] = $csl_key;

			// Add this entry to the set to be rendered. Use the entry's position in the 
			// bibliography as its index.
			$index = array_search($csl_key, $keys_for_post);
			$csl_json_entry = $csl_library->get($csl_key);
			$indexed_csl_values_to_render[$index] = $csl_json_entry;
		}

		// Done. Render the note(s).
		return \Bibcite\Common\CslRenderer::instance()->renderCslEntries(
			$indexed_csl_values_to_render, 
			get_option(\Bibcite\Admin\Admin::BIBCITE_STYLE_NAME),
			get_option(\Bibcite\Admin\Admin::BIBCITE_TEMPLATE_NAME)
		);
	}

	/**
	 * Handle enclosing [bibshow]...[/bibshow] shortcodes. Captures all [bibcite] shortcodes used
	 * inside the [bibshow] shortcode, inserts references and then renders a bibliography at the 
	 * closing tag containing the referenced citations.
	 *
	 * @param array $atts shortcode attributes. Ignored.
	 * @param string $content shortcode content. Ignored.
	 * @return void
	 */
	public function do_bibshow_shortcode( $atts, string $content = null ) {

		\Bibcite\Common\Logger::instance()->debug(
			"Encountered bibshow shortcode with attributes: " . var_export($atts, true)
		);

		// Process the content of this shortcode, in case we encounter any other shortcodes - 
		// in particular, we need to build a list of any [bibcite] shortcodes that define the 
		// contents of this [bibshow] bibliography.
		$processed_content = do_shortcode($content);

		// Find and render the Bibtex entries for each [bibcite] entry in the post.
		////////////////////////////////////////////////////////////////////////////////////////////

		// Work out what post we're in.
		global $post;
		$post_id = $post->ID;
		
		// Do we have an array of [bibcite] entries for this post? If not, nothing to do.
		$keys_for_post;
		if (!array_key_exists ($post_id, $this->post_id_to_bibshow_keys))
			return $content;
		else
			$keys_for_post = $this->post_id_to_bibshow_keys[$post_id];

		// Get or update the library for the source URL
		$url = get_option(\Bibcite\Admin\Admin::LIBRARY_URL);
		$csl_library = self::get_or_update_csl_library($url);
		
		// Find all relevant bibcite keys and place the associated CSL JSON entries in an array.
		// This will preserve the ordering and indexing of the values to be rendered.
		$indices_to_csl_json_objects = array_map(			
			function($key) use ($csl_library) {
				return $csl_library->get($key);
			},
			$keys_for_post
		);

		// Render and return the bibliography.
		$bibliography = \Bibcite\Common\CslRenderer::instance()->renderCslEntries(
			$indices_to_csl_json_objects,
			get_option(\Bibcite\Admin\Admin::BIBSHOW_STYLE_NAME),
			get_option(\Bibcite\Admin\Admin::BIBSHOW_TEMPLATE_NAME)
		);

		return $processed_content . $bibliography;
	}

	/**
	 * Parse shortcode attributes into a well-defined array of settings.
	 *
	 * @param array $atts shortcode attributes
	 * @return array an array of attributes or default values, as appropriate.
	 * @author Keith Houston <keith@shadycharacters.co.uk>
	 * @since 1.0.0
	 */
	private static function parse_shortcode_attributes(array $atts) : array {

		return array(
			self::URL_ATTRIBUTE => 
				$atts["file"] ?? get_option(\Bibcite\Admin\Admin::LIBRARY_URL),
			self::KEYS_ATTRIBUTE => 
				$atts["key"] ?? "",
			self::STYLE_ATTRIBUTE => 
				$atts["style"] ?? get_option(\Bibcite\Admin\Admin::BIBTEX_STYLE_NAME),
			self::TEMPLATE_ATTRIBUTE => 
				$atts["template"] ?? get_option(\Bibcite\Admin\Admin::BIBTEX_TEMPLATE_NAME),
			self::SORT_ATTRIBUTE => 
				$atts["sort"] ?? null,
			self::ORDER_ATTRIBUTE => 
				$atts["order"] ?? "asc"
		);
	}

	/**
	 * Get or update a \Bibcite\Common\CslLibrary instance representing the contents of the 
	 * specified URL.
	 *
	 * @param string $url URL of a Bibtex library to be fetched.
	 * @return \Bibcite\Common\CslLibrary a \Bibcite\Common\CslLibrary containing CSL JSON entries
	 */
	private static function get_or_update_csl_library(string $url) : \Bibcite\Common\CslLibrary {
		
		// Work out the target local filename		
		$slugify = new \Cocur\Slugify\Slugify();
		$filename = implode( 
			DIRECTORY_SEPARATOR, 
			array( 
				plugin_dir_path(dirname(__FILE__)), 
				BIBCITE_SC_CACHE_DIRECTORY, 
				$slugify->slugify($url)
			)
		);

		// Get the current set of stored entries for the named URL.
		$csl_library = new \Bibcite\Common\CslLibrary($url);

		// If the Bibtex file hasn't been downloaded or parsed, do it now. If we succeed in updating
		// the local copy, update our library.
		if (!\Bibcite\Common\Downloader::save_url_to_file($url, $filename)) {
			\Bibcite\Common\Logger::instance()->warn(
				"Failed to get up-to-date Bibtex library from URL (${url}). Using cached entries."
			);
			return $csl_library;
		}

		// We have some new data. Parse the file into Bibtex.
		// KHFIXME: detect file type and handle CSL, RIS as well.
		$bibtex_entries = \Bibcite\Common\BibtexParser::parse_file_to_bibtex($filename);
		\Bibcite\Common\Logger::instance()->info(
			"Retrieved up-to-date Bibtex library from URL (${url}). Parsing..."
		);

		// If we got any entries, update the corresponding library.
		if (sizeof($bibtex_entries) <= 0) {
			\Bibcite\Common\Logger::instance()->warn(
				"No entries found in Bibtex library ($url)."
			);
			return $csl_library;
		}

		// We have some entries to parse. Do so now.
		\Bibcite\Common\Logger::instance()->debug( "Updating Bibtex library ($url)...");
		$converter  = new Converter();
		foreach ($bibtex_entries as $bibtex_entry) {
			try {

				// Convert Bibtex to CSL String, then to a CSL JSON object, then store in 
				// the library
				$csl_json_string = $converter->convert(
					new BibTeX($bibtex_entry["_original"]), 
					new CSL()
				);
				$csl_json_object = json_decode($csl_json_string)[0];
				$csl_library->add_or_update(
					$bibtex_entry["citation-key"], $csl_json_object
				);
			}
			catch (Exception $e) {
				\Bibcite\Common\Logger::instance()->warn(
					"Failed to convert and save Bibtex entry: " . $e->getMessage()
				);
			}
		}
		
		// Done.
		return $csl_library;
	} 
}
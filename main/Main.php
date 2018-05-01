<?php

namespace Bibcite\Main;

require plugin_dir_path( dirname( __FILE__ ) ) . 'vendor/autoload.php';

use Geissler\Converter\Converter;
use Geissler\Converter\Standard\BibTeX\BibTeX;
use Geissler\Converter\Standard\CSL\CSL;

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and handlers for all Bibcite-related 
 * shortcodes.
 *
 * @author Keith Houston <keith@shadycharacters.co.uk>
 * @link https://github.com/OrkneyDullard/Bibcite
 * @package Bibcite\Main
 * @since 1.0.0
 */
class Main {

	// Names of parsed shortcode attributes returned by 
	// parse_shortcode_attributes().
	private const URL_ATTRIBUTE = "file";
	private const KEYS_ATTRIBUTE = "key";
	private const STYLE_ATTRIBUTE = "style";
	private const TEMPLATE_ATTRIBUTE = "template";
	private const SORT_ATTRIBUTE = "sort";
	private const ORDER_ATTRIBUTE = "order";

	// The ID of this plugin.
	private $bibcite;

	// The version of this plugin.
	private $version;

	// A [URL -> CslLibrary] array.
	private static $urls_to_csl_libraries = array();

	// A [post ID -> [key]] array of arrays. Lists the set of unique keys per 
	// post.
	private $post_id_to_bibshow_keys = array();

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $bibcite       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( string $bibcite, string $version ) {

		$this->bibcite = $bibcite;
		$this->version = $version;

	}
	/**
	 * Handle a standalone [bibtex] shortcode. This renders a bibliography at 
	 * the shortcode location, populated only with the specified references.
	 *
	 * @param array|string $atts shortcode attributes, or the empty string
	 * @param string $content shortcode content. Ignored.
	 * @param string $tag shortcode name
	 * @return string|null
	 */
	public function do_bibtex_shortcode( 
		$atts = [], string $content = null, string $tag = ''
	) {

		// Work out what post we're in.
		global $post;
		$post_id = $post->ID;

		\Bibcite\Common\Logger::instance()->debug(
			"Encountered bibtex shortcode in post $post_id with attributes: " . 
			var_export($atts, true)
		);

		// Parse attributes
		$attributes = self::parse_shortcode_attributes($tag, $atts);

		// Get or update the library for the source URL
		$csl_library = self::get_or_update_csl_library(
			$attributes[self::URL_ATTRIBUTE]
		);

		// Get the set of CSL JSON objects to be rendered.
		$csl_entries = array();
		foreach (explode(",", $attributes[self::KEYS_ATTRIBUTE]) as $csl_key) {
			$csl_json_object = $csl_library->get($csl_key);
			if ($csl_json_object) {
				\Bibcite\Common\Logger::instance()->debug(
					"Found CSL library entry: " . $csl_key
				);
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

			// Run a string comparison on the specified field. Invert the 
			// comparison score if we want the sort order to be descending.
			usort(
				$csl_entries,
				function($a, $b) use ($sort, $order) {
					try {						
						$cmp = strcmp(
							var_export($a->{$sort}, true), 
							var_export($b->{$sort}, true)
						);
						return ($order == "asc") ? $cmp : -$cmp;
					}
					catch (Exception $e) {
						\Bibcite\Common\Logger::instance()->warn(
							"Could not sort on CSL attribute '$sort': " . 
							$e->getMessage()
						);
						return 0;
					}
				}
			);
		}

		// Render and return the bibliography.
		\Bibcite\Common\Logger::instance()->info(
			"Rendering keys " . $attributes[self::KEYS_ATTRIBUTE] . 
			" for bibtex shortcode in post $post_id..."
		);
		return \Bibcite\Common\CslRenderer::instance()->renderCslEntries(
			$csl_entries, 
			$attributes[self::STYLE_ATTRIBUTE], 
			$attributes[self::TEMPLATE_ATTRIBUTE]
		);
	}

	/**
	 * Handle a [bibcite] shortcode. When used inside a [bibshow]...[/bibshow] 
	 * shortcode, this inserts a a formatted note to a specified citation or 
	 * citations.
	 *
	 * @param array|string $atts shortcode attributes, or the empty string
	 * @param string $content shortcode content. Ignored.
	 * @param string $tag the name of the shortcode
	 * @return string the string with which the [bibcite] shortcode is to be 
	 * replaced.
	 * 
	 * @author Keith Houston <keith@shadycharacters.co.uk>
	 * @since 1.0.0	
	 * */
	public function do_bibcite_shortcode( 
		$atts = [], string $content = null, string $tag = ''
	) : string  {

		// Work out what post we're in.
		global $post;
		$post_id = $post->ID;

		\Bibcite\Common\Logger::instance()->debug(
			"Encountered bibcite shortcode in post $post_id with attributes: " . 
			var_export($atts, true)
		);
		
		// Do we already have an array of known keys for this post? If not, 
		// create one.
		if (!isset($this->post_id_to_bibshow_keys[$post_id])) {
			$this->post_id_to_bibshow_keys[$post_id] = array();
			\Bibcite\Common\Logger::instance()->debug(
				"Creating new array of [key => rendered entry] for post ${post_id}"
			);
		}

		// Parse attributes
		$attributes = self::parse_shortcode_attributes($tag, $atts);

		// $keys_for_post contains only unique keys within this post in the 
		// order that we encounter them. Separately, build a list of keys *for 
		// this shortcode only* to be rendered and emitted at the end of this 
		// method.
		$keys_for_post = &$this->post_id_to_bibshow_keys[$post_id];
		$keys_for_shortcode = explode(",", $attributes[self::KEYS_ATTRIBUTE]);

		// Do we need to do anything?
		if (count($keys_for_shortcode) <= 0) {
			\Bibcite\Common\Logger::instance()->warn(
				"No keys found. Skipping bibcite shortcode."
			);
			return "";
		}

		// If we're here, we need to render our notes. First, get the library.
		$url = $attributes[self::URL_ATTRIBUTE];
		$csl_library = self::get_or_update_csl_library($url);

		// For each key in this shortcode...		
		$indexed_csl_values_to_render = array();
		foreach ($keys_for_shortcode as $csl_key) {

			// If this key doesn't already exist in the list of keys to be 
			// rendered in the bibliography, add it now.
			if (!in_array($csl_key, $keys_for_post))
				$keys_for_post[] = $csl_key;

			// Add this entry to the set to be rendered. Use the entry's 
			// position in the bibliography as its index.
			$index = array_search($csl_key, $keys_for_post);
			$csl_json_entry = $csl_library->get($csl_key);
			$indexed_csl_values_to_render[$index] = $csl_json_entry;

			// Did we fail to get a valid CSL entry for this key, for whatever 
			// reason?
			if (!isset($csl_json_entry) || $csl_json_entry == false) 
				\Bibcite\Common\Logger::instance()->warn(
					"Could not find CSL entry for key $csl_key in post $post_id. Skipping."
				);		
		}

		// Done. Render the note(s).
		\Bibcite\Common\Logger::instance()->info(
			"Rendering keys " . $attributes[self::KEYS_ATTRIBUTE] . 
			" for bibcite shortcode in post $post_id..."
		);
		return \Bibcite\Common\CslRenderer::instance()->renderCslEntries(
			$indexed_csl_values_to_render, 
			$attributes[self::STYLE_ATTRIBUTE],
			$attributes[self::TEMPLATE_ATTRIBUTE]
		);
	}

	/**
	 * Handle enclosing [bibshow]...[/bibshow] shortcodes. Captures all 
	 * [bibcite] shortcodes used inside the [bibshow] shortcode, inserts 
	 * references and then renders a bibliography at the closing tag containing 
	 * the referenced citations.
	 *
	 * @param array|string $atts shortcode attributes, or the empty string
	 * @param string $content shortcode content. Ignored.
	 * @param string $tag the name of the shortcode
	 * @return void
	 */
	public function do_bibshow_shortcode( 
		$atts = [], string $content = null, string $tag = '' 
	) {

		// Work out what post we're in.
		global $post;
		$post_id = $post->ID;

		\Bibcite\Common\Logger::instance()->debug(
			"Encountered opening bibshow shortcode in post $post_id with attributes: " . 
			var_export($atts, true)
		);
		
		// Process the content of this shortcode, in case we encounter any other 
		// shortcodes - in particular, we need to build a list of any [bibcite] 
		// shortcodes that define the contents of this [bibshow] bibliography.
		$processed_content = do_shortcode($content);

		\Bibcite\Common\Logger::instance()->debug(
			"Encountered closing bibshow shortcode in post $post_id with attributes: " . 
			var_export($atts, true)
		);
		
		// Do we have an array of [bibcite] entries for this post? If not, 
		// nothing to do.
		if (!array_key_exists($post_id, $this->post_id_to_bibshow_keys)) {
			\Bibcite\Common\Logger::instance()->warn(
				"No keys found for bibshow shortcode in post $post_id. Skipping."
			);
			return $processed_content;
		}

		// Parse attributes
		$attributes = self::parse_shortcode_attributes($tag, $atts);

		// Get or update the library for the source URL
		$url = $attributes[self::URL_ATTRIBUTE];
		$csl_library = self::get_or_update_csl_library($url);
		
		// Find all relevant bibcite keys and place the associated CSL JSON 
		// entries in an array. This will preserve the ordering and indexing of 
		// the values to be rendered.
		$keys_for_post = $this->post_id_to_bibshow_keys[$post_id];
		$indices_to_csl_json_objects = array_map(			
			function($key) use ($csl_library) { 
				return $csl_library->get($key);
			},
			$keys_for_post
		);

		// Render and return the bibliography.
		$key_count = sizeof($keys_for_post);
		\Bibcite\Common\Logger::instance()->debug(
			"Rendering $key_count keys for closing bibshow shortcode in post $post_id..."
		);
		$bibliography = 
			\Bibcite\Common\CslRenderer::instance()->renderCslEntries(
				$indices_to_csl_json_objects,
				$attributes[self::STYLE_ATTRIBUTE],
				$attributes[self::TEMPLATE_ATTRIBUTE]
			);

		return $processed_content . $bibliography;
	}

	/**
	 * Parse shortcode attributes into a well-defined array of settings.
	 *
	 * @param string $shortcode shortcode
	 * @param $atts shortcode attributes. If not specified, or not an array, all 
	 * returned values are defaults.
	 * @return array an array of attributes or default values, as appropriate.
	 * @author Keith Houston <keith@shadycharacters.co.uk>
	 * @since 1.0.0
	 */
	private static function parse_shortcode_attributes(
		string $shortcode, $atts
	) : array {

		$style_attribute_default = '';
		switch ($shortcode) {
			case "bibcite":
				$style_attribute_default = 
					get_option(\Bibcite\Admin\Admin::BIBCITE_STYLE_NAME);
				break;
			case "bibshow":
				$style_attribute_default = 
					get_option(\Bibcite\Admin\Admin::BIBSHOW_STYLE_NAME);
				break;
			case "bibtex":
				$style_attribute_default = 
					get_option(\Bibcite\Admin\Admin::BIBTEX_STYLE_NAME);
				break;
		}
		
		$template_attribute_default = '';
		switch ($shortcode) {
			case "bibcite":
				$template_attribute_default = 
					get_option(\Bibcite\Admin\Admin::BIBCITE_TEMPLATE_NAME);
				break;
			case "bibshow":
				$template_attribute_default = 
					get_option(\Bibcite\Admin\Admin::BIBSHOW_TEMPLATE_NAME);
				break;
			case "bibtex":
				$template_attribute_default = 
					get_option(\Bibcite\Admin\Admin::BIBTEX_TEMPLATE_NAME);
				break;
		}

		$defaults = array(
			self::URL_ATTRIBUTE => get_option(\Bibcite\Admin\Admin::LIBRARY_URL),
			self::KEYS_ATTRIBUTE => '',
			self::STYLE_ATTRIBUTE => $style_attribute_default,
			self::TEMPLATE_ATTRIBUTE => $template_attribute_default,
			self::SORT_ATTRIBUTE => null,
			self::ORDER_ATTRIBUTE => "asc"
		);

		return shortcode_atts($defaults, $atts, $shortcode);
	}

	/**
	 * Get or update a \Bibcite\Common\CslLibrary instance representing the 
	 * contents of the specified URL.
	 *
	 * @param string $url URL of a Bibtex library to be fetched.
	 * @return \Bibcite\Common\CslLibrary a \Bibcite\Common\CslLibrary 
	 * containing CSL JSON entries
	 */
	private static function get_or_update_csl_library(
		string $url
	) : \Bibcite\Common\CslLibrary {

		// Have we already created this library during this run? If so, just
		// return it.
		if (isset(self::$urls_to_csl_libraries[$url]))
			return self::$urls_to_csl_libraries[$url];

		// If not, we need to get and record a new CslLibrary for this URL and
		// work out if it needs to be populated.
		$csl_library = new \Bibcite\Common\CslLibrary($url);
		self::$urls_to_csl_libraries[$url] = $csl_library;
		
		// Work out the target local filename
		$slugify = new \Cocur\Slugify\Slugify();
		$filename = implode( 
			DIRECTORY_SEPARATOR, 
			array( 
				plugin_dir_path(dirname(__FILE__)), 
				BIBCITE_CACHE_DIRECTORY, 
				$slugify->slugify($url)
			)
		);

		// If the file has changed recently, download it. If not, just use our
		// cached values.
		if (!\Bibcite\Common\Downloader::save_url_to_file($url, $filename)) {
			\Bibcite\Common\Logger::instance()->debug(
				"No new Bibtex file retrieved from URL (${url}). Using cached database entries."
			);
			return $csl_library;
		}

		// We have some new data. Parse as Bibtex
		\Bibcite\Common\Logger::instance()->info(
			"Retrieved up-to-date Bibtex file from URL (${url}). Parsing..."
		);
		$bibtex_entries = 
			\Bibcite\Common\BibtexParser::parse_file_to_bibtex($filename);

		// If we got any entries, update the corresponding library.
		if (sizeof($bibtex_entries) <= 0) {
			\Bibcite\Common\Logger::instance()->warn(
				"No entries found in Bibtex file ($url). Using cached database entries."
			);
			return $csl_library;
		}

		// We have some entries to parse. Do so now.
		\Bibcite\Common\Logger::instance()->debug(
			"Updating CSL library ($url) with parsed Bibtex entries..."
		);
		$converter  = new Converter();
		foreach ($bibtex_entries as $bibtex_entry) {
			try {

				// Convert Bibtex to CSL String, then to a CSL JSON object.
				$csl_json_string = $converter->convert(
					new BibTeX($bibtex_entry["_original"]), new CSL()
				);
				$csl_json_object = json_decode($csl_json_string)[0];

				// Remove any spurious braces - CSL takes care of capitalisation 
				// itself.
				if (isset($csl_json_object->{'title'}))
					$csl_json_object->{'title'} = \str_replace(
						array("{", "}"), "", $csl_json_object->{'title'}
					);

				// Save the CSL JSON object in our library.
				$csl_library->add_or_update(
					$bibtex_entry["citation-key"], $csl_json_object
				);
			}
			catch (Exception $e) {
				\Bibcite\Common\Logger::instance()->warn(
					"Failed to convert and save Bibtex entry: " . 
					$e->getMessage()
				);
			}
		}
		
		// Done.
		return $csl_library;
	} 
}
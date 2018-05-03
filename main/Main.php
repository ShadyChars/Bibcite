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

	// How long should our transients live (= 30 days)?
	private const TRANSIENT_EXPIRATION_SECONDS = 3600*24*30;

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
	 * @return string shortcode result
	 */
	public function do_bibtex_shortcode( 
		$atts = [], string $content = null, string $tag = ''
	) : string {
		global $post;
		$post_id = $post->ID;		
		$logger = new \Bibcite\Common\ScopedLogger(
			\Bibcite\Common\Logger::instance(), 
			__METHOD__ ." - Post $post_id - "
		);

		$logger->debug(
			"Encountered shortcode with attributes: " . var_export($atts, true)
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
				$logger->debug("Found CSL library entry: " . $csl_key);
				$csl_entries[] = $csl_json_object;
			}
			else
				$logger->warning(
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
						$logger->warning(
							"Could not sort on CSL attribute '$sort': " . 
							$e->getMessage()
						);
						return 0;
					}
				}
			);
		}

		// Render and return the bibliography.
		$logger->info(
			"Rendering keys " . $attributes[self::KEYS_ATTRIBUTE] . "..."
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

		global $post;
		$post_id = $post->ID;		
		$logger = new \Bibcite\Common\ScopedLogger(
			\Bibcite\Common\Logger::instance(), 
			__METHOD__ ." - Post $post_id - "
        );

		$logger->debug(
			"Encountered shortcode with attributes: " . var_export($atts, true)
		);
		
		// Do we already have an array of known keys for this post? If not, 
		// there's no enclosing [bibshow] shortcode.
		if (!isset($this->post_id_to_bibshow_keys[$post_id])) {
			$logger->warn("No enclosing [bibshow] shortcode. Skipping");
			return "";
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
			$logger->warning("No keys found. Skipping shortcode.");
			return "";
		}

		// If we're here, we need to render our notes. First, get the library.
		$csl_library = self::get_or_update_csl_library(
			$attributes[self::URL_ATTRIBUTE]
		);

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
				$logger->warning(
					"Could not find CSL entry for key $csl_key. Skipping."
				);
		}

		// Done. Render the note(s).
		$logger->info(
			"Rendering keys " . $attributes[self::KEYS_ATTRIBUTE] . "..."
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
	 * @param string $content shortcode content. This is processed so as to 
	 * catch any nested [bibcite] shortcodes.
	 * @param string $tag the name of the shortcode
	 * @return string shortcode result
	 */
	public function do_bibshow_shortcode( 
		$atts = [], string $content = null, string $tag = '' 
	) : string {
		global $post;
		$post_id = $post->ID;		
		$logger = new \Bibcite\Common\ScopedLogger(
			\Bibcite\Common\Logger::instance(), 
			__METHOD__ ." - Post $post_id - "
        );

		$logger->debug(
			"Encountered opening shortcode with attributes: " . 
			var_export($atts, true)
		);

		// Initialise our list of keys for this post
		$this->post_id_to_bibshow_keys[$post_id] = array();
		
		// Process the content of this shortcode, in case we encounter any other 
		// shortcodes - in particular, we need to build a list of any [bibcite] 
		// shortcodes that define the contents of this [bibshow] bibliography.
		$processed_content = do_shortcode($content);

		$logger->debug(
			"Encountered closing shortcode with attributes: " . 
			var_export($atts, true)
		);
		
		// Do we have an array of [bibcite] entries for this post? If not, 
		// nothing to do.
		if (sizeof($this->post_id_to_bibshow_keys[$post_id]) == 0) {
			$logger->warning("No bibcite shortcodes found in post. Skipping.");
			return $processed_content;
		}

		// Parse attributes
		$attributes = self::parse_shortcode_attributes($tag, $atts);

		// Get or update the library for the source URL
		$csl_library = self::get_or_update_csl_library(
			$attributes[self::URL_ATTRIBUTE]
		);
		
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
		$logger->debug(
			"Rendering $key_count keys for closing bibshow shortcode..."
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

		// Include the target URL in our scoped logging messages.
		$logger = new \Bibcite\Common\ScopedLogger(
			\Bibcite\Common\Logger::instance(), __METHOD__ ." - $url - "
        );

		// Have we already created this library during this run? If so, just
		// return it.
		if (isset(self::$urls_to_csl_libraries[$url])) {
			$logger->debug("Using existing CslLibrary");
			return self::$urls_to_csl_libraries[$url];
		}

		// If not, we need to get and record a new CslLibrary for this URL.
		$csl_library = new \Bibcite\Common\CslLibrary($url);
		self::$urls_to_csl_libraries[$url] = $csl_library;
		
		// Create a prefix for transients created in this method.
		$slugify = new \Cocur\Slugify\Slugify();
		$transient_prefix_for_url = $slugify->slugify(
			__METHOD__ . "_" . md5($url) . "_"
		);
		
		// Download (or get a cached version of the Bibtex library).
		$bibtex_library_body = \Bibcite\Common\Downloader::get_url($url);

		// Has the library changed since we last parsed it? If so, we need to 
		// parse it. If not, skip the parsing stage.
		$bibtex_library_hash = md5($bibtex_library_body);
		$bibtex_library_hash_transient_name = 
			$transient_prefix_for_url . "bibtex-library-hash";
		$bibtex_library_hash_previous = 
			\Bibcite\Common\Transients::instance()->get_transient(
				$bibtex_library_hash_transient_name
			);
		
		if ($bibtex_library_hash != $bibtex_library_hash_previous)
		{
			$logger->debug(
				"The Bibtex library has changed (old #: " .
				"$bibtex_library_hash_previous; new #: " .
				"$bibtex_library_hash). Parsing..."
			);

			// Parse the library to an array of Bibtex entries.
			$bibtex_entries = 
				\Bibcite\Common\BibtexParser::parse_string_to_bibtex(
					$bibtex_library_body
				);

				// Convert to CSL and record in our CslLibrary.
			$converter = new Converter();		
			foreach ($bibtex_entries as $bibtex_entry) {
				try {

					// Convert Bibtex to CSL String, then to a CSL JSON object.
					$csl_json_string = $converter->convert(
						new BibTeX($bibtex_entry["_original"]), new CSL()
					);
					$csl_json_object = json_decode($csl_json_string)[0];

					// Remove any spurious braces.
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
					$logger->warning(
						"Failed to convert and save Bibtex entry: " . 
						$e->getMessage()
					);
				}
			}

			// Record the hash of the newly-parsed URL.
			\Bibcite\Common\Transients::instance()->set_transient(
				$bibtex_library_hash_transient_name, 
				$bibtex_library_hash,
				self::TRANSIENT_EXPIRATION_SECONDS
			);
		}
				
		// Done.
		return $csl_library;
	} 
}
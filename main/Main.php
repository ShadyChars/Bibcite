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

	// The ID of this plugin.
	private $bibcite_sc;

	// The version of this plugin.
	private $version;

	// A [post ID -> [key => rendered entry]] array of arrays.
	private $post_id_to_rendered_notes_array;

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
		$this->post_id_to_rendered_notes_array = array();
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
			"Encountered bibcite shortcode with attributes: " . implode(", ", $atts)
		);

		// Work out what post we're in.
		global $post;
		$post_id = $post->ID;
		
		// Do we already have an array of [bibcite] entries for this post? If not, create one.
		if ( !array_key_exists ( $post_id, $this->post_id_to_rendered_notes_array ) ) {
			$this->post_id_to_rendered_notes_array[$post_id] = array();
			\Bibcite\Common\Logger::instance()->debug(
				"Creating new array of bibcite keys for post ${post_id}"
			);
		}

		$keys_to_rendered_notes = &$this->post_id_to_rendered_notes_array[$post_id];

		// Extract the key or keys in this [bibcite key=...] shortcode. If we've already seen this 
		// entry, reuse it. This will preserve its position in the [key => entry array]
		$bibcite_key = $atts["key"];
		if ( array_key_exists( $bibcite_key, $keys_to_rendered_notes ) ) {
			return $keys_to_rendered_notes[$bibcite_key];
		}

		// If we're here, render the note.
		$url = get_option(\Bibcite\Admin\Admin::LIBRARY_URL);
		$csl_library = $this->get_or_update_csl_library($url);
		$csl_json_entry = $csl_library->get($bibcite_key);
		$rendered_note = \Bibcite\Common\CslRenderer::instance()->renderCslEntries(
			array($csl_json_entry), 
			get_option(\Bibcite\Admin\Admin::BIBCITE_STYLE_NAME),
			get_option(\Bibcite\Admin\Admin::BIBCITE_TEMPLATE_NAME)
		);
		
		// Save the rendered note in the list and return it;
		$keys_to_rendered_notes[$bibcite_key] = $rendered_note;
		return $keys_to_rendered_notes[$bibcite_key];
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

		\Bibcite\Common\Logger::instance()->debug("Encountered bibshow shortcode");

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
		$keys_to_rendered_notes;
		if (!array_key_exists ($post_id, $this->post_id_to_rendered_notes_array))
			return $content;
		else
			$keys_to_rendered_notes = $this->post_id_to_rendered_notes_array[$post_id];

		// Get or update the library for the source URL
		$url = get_option(\Bibcite\Admin\Admin::LIBRARY_URL);
		$csl_library = $this->get_or_update_csl_library($url);
		
		// Find all relevant bibcite keys and place the associated CSL JSON entries in an array.
		$csl_entries = array();
		foreach ($keys_to_rendered_notes as $bibcite_key => $rendered_entry) {
			$csl_json_object = $csl_library->get($bibcite_key);
			if ($csl_json_object) {
				$csl_entries[] = $csl_json_object;
				\Bibcite\Common\Logger::instance()->debug(
					"Found CSL library entry: " . $bibcite_key
				);
			} else {
				\Bibcite\Common\Logger::instance()->warn(
					"Could not find CSL library entry: " . $bibcite_key
				);
			}
		}

		// Render and return the bibliography.
		$bibliography = \Bibcite\Common\CslRenderer::instance()->renderCslEntries(
			$csl_entries, 
			get_option(\Bibcite\Admin\Admin::BIBSHOW_STYLE_NAME),
			get_option(\Bibcite\Admin\Admin::BIBSHOW_TEMPLATE_NAME)
		);

		return $processed_content . $bibliography;
	}

	/**
	 * Handle a standalone [bibtex] shortcode. This renders a bibliography at the shortcode 
	 * location, populated only with the reference keys.
	 *
	 * @param array $atts shortcode attributes. Only "key=<key1>[,<key2>[,...]]" is supported.
	 * @param string $content shortcode content. Ignored.
	 * @return string|null
	 */
	public function do_bibtex_shortcode( array $atts, string $content = null ) {

		// Get or update the library for the source URL
		$url = get_option(\Bibcite\Admin\Admin::LIBRARY_URL);
		$csl_library = $this->get_or_update_csl_library($url);

		// Extract the set of requested keys.
		// TK: allow more sophisticated key queries
		$keys = null;
		try {
			$keys = $atts["key"];
			\Bibcite\Common\Logger::instance()->debug(
				"Encountered bibtex shortcode with keys: " . $keys
			);
		}
		catch (Exception $e) {
			\Bibcite\Common\Logger::instance()->error(
				"Failed to get keys from [bibtex] shortcode attributes: " . $e->getMessage() . "."
			);
			return null;
		}

		// Get the set of entries to be rendered and convert to CSL JSON.		
		$csl_entries = array();
		foreach (explode(",", $keys) as $bibtex_key ) {
			$csl_json_object = $csl_library->get($bibtex_key);
			if (!$csl_json_object) {
				\Bibcite\Common\Logger::instance()->warn(
					"Could not find Bibtex library entry: " . $bibtex_key
				);
				continue;
			}

			// Record the entry.
			\Bibcite\Common\Logger::instance()->debug("Found Bibtex library entry: " . $bibtex_key);
			$csl_entries[] = $csl_json_object;
		}

		// Do we need to sort the entries?
		/*$sort = isset($atts['sort']) ? $atts['sort'] : false;
		if ($sort)
		{
			switch ($sort)
			{
				case 'year':
					array_
			}
		}*/

		// Render and return the bibliography.
		return \Bibcite\Common\CslRenderer::instance()->renderCslEntries(
			$csl_entries, 
			get_option(\Bibcite\Admin\Admin::BIBTEX_STYLE_NAME),
			get_option(\Bibcite\Admin\Admin::BIBTEX_TEMPLATE_NAME)
		);
	}

	/**
	 * Get or update a \Bibcite\Common\CslLibrary instance representing the contents of the 
	 * specified URL.
	 *
	 * @param string $url URL of a Bibtex library to be fetched.
	 * @return \Bibcite\Common\CslLibrary a \Bibcite\Common\CslLibrary containing CSL JSON entries
	 */
	private function get_or_update_csl_library(string $url) : \Bibcite\Common\CslLibrary {
		
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
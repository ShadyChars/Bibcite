<?php

require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes\class-bibcite-logger.php';
require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes\class-bibcite-library.php';
require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes\class-bibcite-downloader.php';
require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes\class-bibcite-parser.php';
require plugin_dir_path( dirname( __FILE__ ) ) . 'vendor\autoload.php';

use Geissler\Converter\Converter;
use Geissler\Converter\Standard\BibTeX\BibTeX;
use Geissler\Converter\Standard\CSL\CSL;

/**
 * The public-facing functionality of the plugin.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    Bibcite_SC
 * @subpackage Bibcite_SC/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Bibcite_SC
 * @subpackage Bibcite_SC/public
 * @author     Your Name <email@example.com>
 */
class Bibcite_SC_Public {

	// TODO: make this a setting.
	const LIBRARY_URL = 'https://www.dropbox.com/s/m1lgya889qnz081/library.bib?dl=1';

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $bibcite_sc    The ID of this plugin.
	 */
	private $bibcite_sc;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * A post ID -> [bibcite entries] array.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $post_id_to_bibcite_keys_array    A post ID -> [bibcite entries] array.
	 */
	private $post_id_to_bibcite_keys_array;

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
		$this->post_id_to_bibcite_keys_array = array();
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
	 * @return void
	 */
	public function do_bibcite_shortcode( array $atts, string $content = null ) {

		Bibcite_Logger::instance()->debug(
			"Encountered bibcite shortcode with attributes: " . implode(", ", $atts)
		);

		// Work out what post we're in.
		global $post;
		$post_id = $post->ID;
		
		// Do we already have an array of [bibcite] entries for this post? If not, create one.
		if ( !array_key_exists ( $post_id, $this->post_id_to_bibcite_keys_array ) ) {
			$this->post_id_to_bibcite_keys_array[$post_id] = array();
			Bibcite_Logger::instance()->debug(
				"Creating new array of bibcite keys for post ${post_id}"
			);
		}

		$bibcite_indices_to_keys = &$this->post_id_to_bibcite_keys_array[$post_id];

		// Extract the key or keys in this [bibcite key=...] shortcode. If we've already seen this 
		// entry, reuse it.
		$bibcite_key = $atts["key"];
		if ( array_key_exists( $bibcite_key, $bibcite_indices_to_keys ) ) {

			// KHFIXME: deal w/ multiple references in a single citation. Put both in the 
			// bibliography under a single numberered note, or have *n* notes separated by commas?

			// KHFIXME: if we aren't inside a [bibshow] shortcode, we shouldn't show anything. How
			// to handle this? Do we leave out all the processing here and carry it out in [bibshow]
			// instead, replacing [bibcite] shortcodes where we find them?

			$bibcite_indices_to_keys[] = $bibcite_key;
			return "[Existing key: ${bibcite_key}]";
		}

		// If not, add a new entry to the list.
		$bibcite_indices_to_keys[] = $bibcite_key;
		$bibcite_index = count ( $bibcite_indices_to_keys ) - 1;

		// Increment the reference index and emit the link.

		// KHFIXME: how to make this entry templateable? Should we load the entire library and
		// make the full citation data available here, so that the entry can be templated by, e.g.
		// author surname and year?

		return "[New key: ${bibcite_key}; index: ${bibcite_index}]";
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

		Bibcite_Logger::instance()->debug("Encountered bibshow shortcode");

		// Process the content of this shortcode, in case we encounter any other shortcodes
		$processed_content = do_shortcode($content);

		// Get or update the library for the source URL
		$url = Bibcite_SC_Public::LIBRARY_URL;
		$bibtex_library = $this->get_or_update_bibtex_library($url);

		// Find and render the Bibtex entries for each [bibcite] entry in the post.
		////////////////////////////////////////////////////////////////////////////////////////////

		// Work out what post we're in.
		global $post;
		$post_id = $post->ID;
		
		// Do we have an array of [bibcite] entries for this post? If not, nothing to do.
		$bibcite_indices_to_keys;
		if ( !array_key_exists ( $post_id, $this->post_id_to_bibcite_keys_array ) )
			return $content;
		else
			$bibcite_indices_to_keys = $this->post_id_to_bibcite_keys_array[$post_id];

		// Set up CiteProc and Geissler\Converter (currently being served from my repo).
		$style = Seboettg\CiteProc\StyleSheet::loadStyleSheet("din-1505-2");
		$citeProc = new Seboettg\CiteProc\CiteProc($style);
    	$converter  = new Converter();

		// Run through the template engine to produce the bibliography and append to the content.
		$bibliography = "";
		foreach ($bibcite_indices_to_keys as $bibcite_index => $bibcite_key) {
			$bibcite_value = $bibtex_library->get($bibcite_key);
			$csl_json_value = $converter->convert(new BibTeX($bibcite_value), new CSL());

			// TODO: concatenate all relevant entries and render as a bibliography? Or use citation
			// mode to control numbering more strictly?
			$rendered_citation = $citeProc->render(json_decode($csl_json_value), "citation");
			$bibliography .= "<p>${rendered_citation}</p>";
		}

		return $processed_content . "<p>${bibliography}</p>";
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
		$url = Bibcite_SC_Public::LIBRARY_URL;
		$bibtex_library = $this->get_or_update_bibtex_library($url);

		// Extract the set of requested keys.
		// TK: allow more sophisticated key queries
		$keys = null;
		try {
			$keys = $atts["key"];
			Bibcite_Logger::instance()->debug("Encountered bibtex shortcode with keys: " . $keys);
		}
		catch (Exception $e) {
			Bibcite_Logger::instance()->error(
				"Failed to get keys from [bibtex] shortcode attributes: " . $e->getMessage() . "."
			);
			return null;
		}

		// Get the set of entries to be rendered and convert to CSL JSON.		
		$converter  = new Converter();
		$csl_entries = array();
		foreach (explode(",", $keys) as $bibtex_key ) {
			$bibtex_value = $bibtex_library->get($bibtex_key);
			if ($bibtex_value) {
				try {
					Bibcite_Logger::instance()->debug("Found Bibtex library entry: " . $bibtex_key);
					$csl_entries[] = $converter->convert(new BibTeX($bibtex_value), new CSL());
				} catch (Exception $e) {
					Bibcite_Logger::instance()->error(
						"Exception when converting Bibtex to CSL: $bibtex_value \nException: "
						. $e->getMessage()
					);
				}
			} else {
				Bibcite_Logger::instance()->warn(
					"Could not find Bibtex library entry: " . $bibtex_key
				);
				continue;
			}
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

		// Has the caller specified a custom template?

		// Render the entries with the specified CiteProc style
		$style = Seboettg\CiteProc\StyleSheet::loadStyleSheet("chicago-fullnote-bibliography-16th-edition");
		$citeProc = new Seboettg\CiteProc\CiteProc($style);
		$rendered_entries = array();
		$index = 1;
		foreach ($csl_entries as $csl_entry) {
			try {
				// Decode the JSON to an array
				$csl_associative_array = json_decode($csl_entry);

				// Save the rendered entry as an array
				// KHFIXME: make these key values constant. What other values are necessary?
				// KHFIXME: factor into a separate method - use for both single and multiple 
				// entries.
				$rendered_entries[] = array(
					"note_indices" => null,			// no referring notes
					"citation_index" => $index++,	// 1-based indexing
					"citation_key" => $csl_associative_array[""],
					"csl_citation" => $csl_associative_array,
					"rendered_citation" => 
						$citeProc->render($csl_associative_array, "bibliography")
				);
			} catch (Exception $e) {
				Bibcite_Logger::instance()->error(
					"Exception when rendering CSL: $csl_entry\nException: " . $e->getMessage()
				);
			}
		}

		// Lastly, run the rendered entries through our Twig template to generate the bibliography.

		return $bibliography;
	}

	/**
	 * Get or update a Bibcite_Library instance representing the contents of the specified URL.
	 *
	 * @param string $url URL of a Bibtex library to be fetched.
	 * @return Bibcite_Library
	 */
	private function get_or_update_bibtex_library($url) {
		
		// Work out the target local filename		
		$slugify = new Cocur\Slugify\Slugify();
		$filename = implode( 
			DIRECTORY_SEPARATOR, 
			array( 
				plugin_dir_path(dirname(__FILE__)), 
				BIBCITE_SC_CACHE_DIRECTORY, 
				$slugify->slugify($url)
			)
		);

		// Get the current set of stored entries for the named URL.
		$bibtex_library = new Bibcite_Library($url);

		// If the Bibtex file hasn't been downloaded or parsed, do it now. If we succeed in updating
		// the local copy, update our library.
		if (Bibcite_Downloader::save_url_to_file($url, $filename)) {

			// Parse the file.
			$bibtex_entries = Bibcite_Parser::parse_file_to_bibtex($filename);
			Bibcite_Logger::instance()->warn(
				"Retrieved up-to-date Bibtex library from URL (${url}). Parsing..."
			);

			// If we got any entries, update the corresponding library.
			if (sizeof($bibtex_entries) <= 0) {
				Bibcite_Logger::instance()->warn("No entries found in Bibtex library ($url).");	
			} else {
				Bibcite_Logger::instance()->debug("Updating Bibtex library ($url)...");	
				foreach ($bibtex_entries as $bibtex_entry)
					$bibtex_library->add_or_update(
						$bibtex_entry["citation-key"], $bibtex_entry["_original"]
					);
			}
		} else {
			Bibcite_Logger::instance()->warn(
				"Failed to get up-to-date Bibtex library from URL (${url}). Using cached entries."
			);
		}

		return $bibtex_library;
	} 
}
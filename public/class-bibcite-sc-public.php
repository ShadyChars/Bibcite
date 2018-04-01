<?php

include_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes\class-bibcite-logger.php';
include_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes\class-bibcite-library.php';
include_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes\class-bibcite-downloader.php';
include_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes\class-bibcite-parser.php';
include_once plugin_dir_path( dirname( __FILE__ ) ) . 'vendor\autoload.php';

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
	 * @var      string    $version    The current version of this plugin.
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
	 * Handle a [bibcite] shortcode.
	 *
	 * @since    1.0.0
	 */
	public function do_bibcite_shortcode( $atts, $content = null ) {

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
			$bibcite_indices_to_keys[] = $bibcite_key;
			return "[Existing key: ${bibcite_key}]";
		}

		// If not, add a new entry to the list.
		$bibcite_indices_to_keys[] = $bibcite_key;
		$bibcite_index = count ( $bibcite_indices_to_keys ) - 1;

		// Increment the reference index and emit the link.
		return "[New key: ${bibcite_key}; index: ${bibcite_index}]";
	}

	/**
	 * Handle a [bibshow] ...[/bibshow] shortcode.
	 *
	 * @since    1.0.0
	 */
	public function do_bibshow_shortcode( $atts, $content = null ) {

		Bibcite_Logger::instance()->debug("Encountered bibshow shortcode");

		// Process the content of this shortcode, in case we encounter any other shortcodes
		$processed_content = do_shortcode($content);

		// Work out the target local filename
		$url = Bibcite_SC_Public::LIBRARY_URL;
		$slugify = new Cocur\Slugify\Slugify();
		$filename = plugin_dir_path( dirname( __FILE__ ) ) . 'cache\\' .$slugify->slugify($url);

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
					$bibtex_library->add_or_update($bibtex_entry);
			}
		} else {
			Bibcite_Logger::instance()->warn(
				"Failed to get up-to-date Bibtex library from URL (${url}). Using cached entries."
			);
		}

		// Find the Bibtex entries for each [bibcite] entry in the post.
		///////////////////////////////////////////////////////////////

		// Work out what post we're in.
		global $post;
		$post_id = $post->ID;
		
		// Do we have an array of [bibcite] entries for this post? If not, nothing to do.
		$bibcite_indices_to_keys;
		if ( !array_key_exists ( $post_id, $this->post_id_to_bibcite_keys_array ) )
			return $content;
		else
			$bibcite_indices_to_keys = $this->post_id_to_bibcite_keys_array[$post_id];

		// Run through the template engine to produce the bibliography and append to the content.
		$bibliography = "";
		foreach ($bibcite_indices_to_keys as $bibcite_index => $bibcite_key) {
			$bibcite_value = serialize($bibtex_library->get($bibcite_key));
			$bibliography .= "[Key: ${bibcite_key}; value: ${bibcite_value}]";
		}

		return $processed_content . "<p>${bibliography}</p>";
	}
}

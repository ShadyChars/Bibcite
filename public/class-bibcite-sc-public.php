<?php

include_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes\class-bibcite-logger.php';

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
		$bibcite_indices_to_keys;
		if ( array_key_exists ( $post_id, $this->post_id_to_bibcite_keys_array ) )
			$bibcite_indices_to_keys = $this->post_id_to_bibcite_keys_array[$post_id];
		else
		{
			$bibcite_indices_to_keys = array();
			$this->post_id_to_bibcite_keys_array[$post_id] = $bibcite_indices_to_keys;
		}

		// Extract the key or keys in this [bibcite key=...] shortcode, add it to the array, and
		// get the resultant index.
		$bibcite_key = $atts["key"];
		array_push ( $bibcite_indices_to_keys, $bibcite_key );
		end ( $bibcite_indices_to_keys );
		$bibcite_index = key ( $bibcite_indices_to_keys );

		// Increment the reference index and emit the link.
		return "[Key: " . $bibcite_key . "; index: " . $bibcite_index . "]";
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

		// Compile a list of all [bibcite] entries for this post

		// If the Bibtex file hasn't been downloaded or parsed, do it now.

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
		foreach ($bibcite_indices_to_keys as $bibcite_index => $bibcite_key)
			$bibliography .= "[Key: " . $bibcite_key . "; index: " . $bibcite_index . "]";

		return $processed_content . "<p>" . $bibliography . "</p>";
	}
}

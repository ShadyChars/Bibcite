<?php

require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes\class-bibcite-logger.php';
require plugin_dir_path( dirname( __FILE__ ) ) . 'vendor\autoload.php';

/**
 * Maintain a persistent library of Bibtex entries in the Wordpress DB.
 */
class Bibcite_Library
{
	// Source URL and corresponding table name.
	private $name;
	private $table_name;

	/**
	 * Create a DB table for the specified name, if one does not already exist.
	 */
	public function __construct($name) {

		global $wpdb;

		// Record the requested table name, then create a DB-friendly, prefixed version.
		$this->name = $name;
		$this->table_name = $wpdb->prefix . BIBCITE_SC_PREFIX . "_" . md5($name);
		Bibcite_Logger::instance()->debug("Using table ($name) with name ($this->table_name).");

		// Create the DB, if it doesn't already exist.		
		$charset_collate = $wpdb->get_charset_collate();
		$sql = "CREATE TABLE IF NOT EXISTS $this->table_name (
			citation_key varchar(128) NOT NULL,
			bibtex text NOT NULL,
			PRIMARY KEY  (citation_key)
		) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
	}

	/**
	 * Get the name of this library.
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * Add a Bibtex entry.
	 *
	 * @param string $citation_key the citation key that identifies the entry
	 * @param string $bibtex a textual Bibtex entry
	 * @return void
	 */
	public function add_or_update($citation_key, $bibtex) {
		global $wpdb;
		$wpdb->replace( 
			$this->table_name, 
			array( 'citation_key' => $citation_key, 'bibtex' => $bibtex ) 
		);
	}

	/**
	 * Get a single Bibtex entry from the library corresponding to a supplied key.
	 *
	 * @param string $citation_key the citation key to search for
	 * @return string|false returns the Bibtex entry if found and false if not.
	 */
	public function get($citation_key) {
		global $wpdb;
		$row = $wpdb->get_row( 
			"SELECT * FROM $this->table_name WHERE citation_key='$citation_key'" 
		);
		return ($row) ? $row->bibtex : false;
	}

	public function clear() {

		// Clear all stored entries 
	}
}	
<?php

include_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes\class-bibcite-logger.php';

class Bibcite_Library
{
	// TODO: make this a setting.
	const LIBRARY_URL = 'https://www.dropbox.com/s/m1lgya889qnz081/library.bib?dl=1';

	// Prefix used for all our transient values.
	const BIBCITE_PREFIX = 'BIBCITE_SC';

	// Option names.
	const OPTION_LAST_UPDATED = Bibcite_Library::BIBCITE_PREFIX . "_LAST_UPDATED";

	// How long should we wait between HTTP requests?
	private static $HTTP_DORMANCY;

	// Hold an instance of the class
	private static $instance;

	// Option values, if known.
	private $last_updated;

	// The singleton method
	public static function instance()
	{
		if (!isset(self::$instance)) {
			self::$instance = new Bibcite_Library();

			// Static initialisation.
			self::$HTTP_DORMANCY = new DateInterval("PT5S");
		}
		return self::$instance;
	}

	/**
	 * Read cached options from the WP options database.
	 */
	private function __construct() {

		// Read options, if available.
		$last_updated = get_option( Bibcite_Library::OPTION_LAST_UPDATED );
		if ( $last_updated ) {
			$this->last_updated = new DateTime($last_updated);
		}

		Bibcite_Logger::instance()->info(
			"Library last updated: " . var_export($this->last_updated, true)
		);
	}

	/**
	 * Get a single Bibtex entry from the library.
	 */
	public function get_bibtex_entry($bibtex_key) {

		// Make sure our library is up to date.
		$this->update_library();

		// Do we have the requested item as a transient?
		$transient_key = Bibcite_Library::BIBCITE_PREFIX . "_" . $bibtex_key;
		$bibtex_value = get_transient ( $transient_key );
		if ( $bibtex_value !== false )		
			return $bibtex_value;
		else
			return null;
	}

	/**
	 * Ensures our local library is up to date.
	 */
	private function update_library() {

		// If we last updated our library within the specifed $HTTP_DORMANCY period, do nothing.
		if ( $this->last_updated && 
			($this->last_updated < (new DateTime() - self::$HTTP_DORMANCY))
		) {
			Bibcite_Logger::instance()->debug(
				"Library last updated within " . self::$HTTP_DORMANCY . ". Skipping update."
			);
			return;
		}

		// We need to update the library. Get the headers for the library and work out if it's 
		// changed since we last updated it. 
		// create curl resource 
		$library_headers = null;
		try {
			Bibcite_Logger::instance()->info(
				"Library has not been updated in the past " . 
				self::$HTTP_DORMANCY->format( '%H:%I:%S' ) . 
				". Getting headers from " .
				Bibcite_Library::LIBRARY_URL . 
				"..."
			);

			$ch = curl_init(); 
			curl_setopt($ch, CURLOPT_URL, Bibcite_Library::LIBRARY_URL); 
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);		// Return rather than log the result
			curl_setopt($ch, CURLOPT_FILETIME, true);			// Get the time the resource changed
			curl_setopt($ch, CURLOPT_NOBODY, true);				// Exclude the body
			curl_setopt($ch, CURLOPT_HEADER, true);				// Include the headers
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);		// Follow redirection
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);	// Ignore SSL certs
			$library_headers = curl_exec($ch); 			
			$curl_error = curl_error($ch);
			$curl_info = curl_getinfo($ch);
			curl_close($ch);

			// If there's an error, report as much info as possible.
			if ($curl_error) {
				Bibcite_Logger::instance()->error("Curl error: ${curl_error}");	
				if ($curl_info)
					Bibcite_Logger::instance()->error("Curl info: ". var_export($curl_info, true));	
			}

		} catch (Exception $e) {
			Bibcite_Logger::instance()->error(
				"Failed to retrieve library from URL (" . Bibcite_Library::LIBRARY_URL . "): " . 
				$e->getMessage() . 
				" Using cached library entries."
			);
			return;
		}

		// Has the library changed since we last downloaded it? If not, just use it as is.
		Bibcite_Logger::instance()->debug(
			"Library headers: \n" . var_export($library_headers, true)
		);
		
		// Download the full library and parse it.
		
		// Update our last-updated date/time.

	}
}
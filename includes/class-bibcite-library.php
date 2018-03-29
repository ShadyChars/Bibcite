<?php

include_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes\class-bibcite-logger.php';

class Bibcite_Library
{
	// TODO: make this a setting.
	const LIBRARY_URL = 'https://www.dropbox.com/s/m1lgya889qnz081/library.bib?dl=1';

	// Prefix used for all our transient and option values.
	const BIBCITE_PREFIX = 'BIBCITE_SC';

	// Option names.
	const OPTION_LAST_ETAG = Bibcite_Library::BIBCITE_PREFIX . "_LAST_ETAG";
	const OPTION_LAST_DOWNLOADED_TIME = Bibcite_Library::BIBCITE_PREFIX . "_LAST_DOWNLOADED_TIME";

	// How long should we wait between HTTP requests?
	const HTTP_DORMANCY_SECONDS = 300;

	// Hold an instance of the class.
	private static $instance;

	// Option values, if known.
	private $last_etag;
	private $last_downloaded_time;

	// The singleton method
	public static function instance()
	{
		if (!isset(self::$instance)) {
			self::$instance = new Bibcite_Library();
		}
		return self::$instance;
	}

	/**
	 * Read cached options from the WP options database.
	 */
	private function __construct() {

		// Read options, if available.
		$this->last_etag = get_option( Bibcite_Library::OPTION_LAST_ETAG );
		if ( $this->last_etag != false ) {
			Bibcite_Logger::instance()->info( "Last library ETag: " . $this->last_etag );
		}

		$last_downloaded_string = get_option( Bibcite_Library::OPTION_LAST_DOWNLOADED_TIME );
		if ( $last_downloaded_string != false) {
			$this->last_downloaded_time = intval( $last_downloaded_string );
			Bibcite_Logger::instance()->info(
				"Library last downloaded: " . date ( DATE_RFC850, $this->last_downloaded_time )
			);
		}		
	}

	/**
	 * Get a single Bibtex entry from the library.
	 */
	public function get_bibtex_entry($bibtex_key) {

		// Make sure our library is up to date.
		$this->update_library();

		// Do we have the requested item as a transient? 
		// KHFIXME: should we use a proper DB instead? We don't necessarily want these to expire.
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

		// If we last updated our library within the specifed HTTP_DORMANCY period, do nothing.
		if ( isset($this->last_downloaded_time) && 
			( $this->last_downloaded_time >= (time() - Bibcite_Library::HTTP_DORMANCY_SECONDS ) )
		) {
			Bibcite_Logger::instance()->debug(
				"Library last downloaded within " . 
				Bibcite_Library::HTTP_DORMANCY_SECONDS .
				" seconds. Skipping update."
			);
			return;
		}

		Bibcite_Logger::instance()->info( 
			"Updating library from URL (" . Bibcite_Library::LIBRARY_URL . ")..." 
		);

		// Get the library, but only if it has an ETag different from the last one we saw.
		$library_body = null;
		$library_headers = [];
		$http_code = null;
		$curl_error = null;
		try {
			$ch = curl_init(); 
			curl_setopt($ch, CURLOPT_URL, Bibcite_Library::LIBRARY_URL); 
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);		// Return rather than log the result
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);		// Follow redirection
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);	// Ignore SSL certs

			// Capture headers.
			curl_setopt(
				$ch, 
				CURLOPT_HEADERFUNCTION,
				function($curl, $header) use (&$library_headers)
				{
					$len = strlen($header);
					$header = explode(':', $header, 2);
					if (count($header) < 2) // ignore invalid headers
						return $len;

					$name = strtolower(trim($header[0]));
					$value = trim($header[1]);
					if (!array_key_exists($name, $library_headers))
						$library_headers[$name] = [$value];
					else
						$library_headers[$name][] = $value;

					return $len;
				}
			);

			// If we don't have an ETag, we'll get the complete library.
			if ( $this->last_etag )
				curl_setopt( $ch, CURLOPT_HTTPHEADER, array ( "If-None-Match: $this->last_etag" ) );
			
			$library_body = curl_exec($ch);		
			$curl_error = curl_error($ch);
			$http_code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
			curl_close($ch);

		} catch (Exception $e) {
			Bibcite_Logger::instance()->error(
				"Failed to get library from URL (" . Bibcite_Library::LIBRARY_URL . "): " . 
				$e->getMessage() . 
				" Using cached library entries."
			);
			return;
		}

		// If there's an error, report as much info as possible and quit now.
		if ($curl_error) {
			Bibcite_Logger::instance()->error(
				"Error getting library: ${curl_error}. Using cached library entries."
			);					
			return;
		}

		// The request succeeded. Update our ETag and our last GET datetime.
		$this->last_etag = 
			array_key_exists( "etag", $library_headers ) ? $library_headers["etag"][0] : null;
		$this->last_updated_datetime = time();
		update_option( Bibcite_Library::OPTION_LAST_ETAG, $this->last_etag );
		update_option( Bibcite_Library::OPTION_LAST_DOWNLOADED_TIME, $this->last_updated_datetime );

		$last_updated_string = date ( DATE_RFC850, $this->last_updated_datetime	);
		Bibcite_Logger::instance()->debug( 
			"Writing Etag ($this->last_etag) and library update time ($last_updated_string)."
		);

		// Did we get a new copy of the library? If not, nothing more to do.
		if ( strlen( $library_body ) <= 0 ) {
			Bibcite_Logger::instance()->debug( "No new library received. Using existing library." );
			return;
		}

		// Save the new library and parse it into the DB.
		Bibcite_Logger::instance()->debug( "Received new library. Parsing..." );

		// KHFIXME: TK
	}
}
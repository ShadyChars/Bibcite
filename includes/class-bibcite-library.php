<?php

include_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes\class-bibcite-logger.php';
include_once plugin_dir_path( dirname( __FILE__ ) ) . 'vendor\autoload.php';

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
		$transient_key = Bibcite_Library::BIBCITE_PREFIX . "_" . $bibtex_key;
		$bibtex_value = get_transient ( $transient_key );
		if ( $bibtex_value !== false )
			return $bibtex_value;
		else
			return null;

		// KHFIXME: parse/transform here? Or somewhere else?
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

		// We'll write the library to a predictable filename.
		$slugify = new Cocur\Slugify\Slugify();
		$cache_directory = plugin_dir_path( dirname( __FILE__ ) ) . 'cache\\';
		if (!is_dir($cache_directory))
			mkdir($cache_directory);
		$library_filename = $cache_directory . $slugify->slugify(Bibcite_Library::LIBRARY_URL);

		Bibcite_Logger::instance()->info( 
			"Getting library from URL (" . 
			Bibcite_Library::LIBRARY_URL . 
			") and saving to file (${library_filename})..."
		);	

		// Get the library, but only if it has an ETag different from the last one we saw.
		$library_headers = [];
		$curl_error = null;
		$start_get_time = time();
		try {
			
			$fp = fopen($library_filename, 'w');		
			$ch = curl_init(); 
			curl_setopt($ch, CURLOPT_URL, Bibcite_Library::LIBRARY_URL); 
			curl_setopt($ch, CURLOPT_FILE, $fp);				// Write to file			
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
			{
				Bibcite_Logger::instance()->debug( "Using ETag ($this->last_etag)" );
				curl_setopt( $ch, CURLOPT_HTTPHEADER, array ( "If-None-Match: $this->last_etag" ) );
			}
			
			curl_exec($ch);		
			$curl_error = curl_error($ch);
			curl_close($ch);

		} catch (Exception $e) {
			Bibcite_Logger::instance()->error(
				"Failed to get library from URL (" . Bibcite_Library::LIBRARY_URL . "): " . 
				$e->getMessage() . 
				" Skipping update."
			);
			return;
		}

		// If there's an error, report as much info as possible and quit now.
		if ($curl_error) {
			Bibcite_Logger::instance()->error(
				"Error getting library: ${curl_error}. Skipping update."
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
		$file_mtime = filemtime($library_filename);
		if ( !$file_mtime || $file_mtime >= $start_get_time ) {
			Bibcite_Logger::instance()->debug( 
				"Cached file has not changed. Using existing library." 
			);
			return;
		}

		// Parse the library into entries
		Bibcite_Logger::instance()->debug( "Received new library. Parsing..." );

		$start_parse_time = time();
		$parser = new RenanBr\BibTexParser\Parser();          // Create a Parser
		$listener = new RenanBr\BibTexParser\Listener();      // Create and configure a Listener
		$parser->addListener($listener); // Attach the Listener to the Parser
		$parser->parseFile($library_filename);   // or parseString($library_body)
		$entries = $listener->export();  // Get processed data from the Listener
		
		$parse_duration = time() - $start_parse_time;
		$entry_count = sizeof($entries);
		Bibcite_Logger::instance()->debug( 
			"Parsed ${entry_count} entries from library in ${parse_duration} seconds." 
		);

		// Write all entries to the DB.
	}
}
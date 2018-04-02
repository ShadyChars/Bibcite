<?php

include_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes\class-bibcite-logger.php';
include_once plugin_dir_path( dirname( __FILE__ ) ) . 'vendor\autoload.php';

/**
 * Parses a local Bibtex file and returns a list of parsed entries.
 */
class Bibcite_Parser
{
	/**
	 * Parse a local file into an array of strings containing individual Bibtex entries.
	 *
	 * @param string $filename
	 * @return array an array of associative arrays, where each item is a parsed Bibtex entry. The
	 * Bibtex itself is placed in an '_original' item.
	 */
	public static function parse_file_to_bibtex($filename) {
		
		$start_parse_time = time();

		// Create and run a parser. We only care about 
		$entries = array();
		try {
			$parser = new RenanBr\BibTexParser\Parser();
			$listener = new RenanBr\BibTexParser\Listener();
			$parser->addListener($listener);
			$parser->parseFile($filename);
			$entries = $listener->export();
		}
		catch (Exception $e) {
			Bibcite_Logger::instance()->error(
				"Failed to parse file (${filename}): " . $e->getMessage() . "."
			);
		}	
			
		// Log and return results
		$parse_duration = time() - $start_parse_time;
		$entry_count = sizeof($entries);
		Bibcite_Logger::instance()->debug( 
			"Parsed ${entry_count} entries from ${filename} in ${parse_duration} seconds." 
		);
		
		return $entries;
	}
}
<?php

namespace Bibcite\Common;

require plugin_dir_path(dirname(__FILE__)) . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

/**
 * Parses a local Bibtex file and returns a list of parsed entries.
 *
 * @author Keith Houston <keith@shadycharacters.co.uk>
 * @link https://github.com/ShadyChars/Bibcite
 * @package Bibcite\Common
 * @since 1.0.0
 */
class BibtexParser
{
    /**
     * Parse a string describing a Bibtex library into an array of associative
     * arrays, each one of which contains a single parsed Bibtex entry.
     *
     * @param string $str a Bibtex library as a string
     * @return array an array of associative arrays, where each associative 
     * array is a parsed Bibtex entry. The Bibtex string itself is placed in an 
     * '_original' item.
     */
    public static function parse_string_to_bibtex(string $str): array
    {
        $logger = new ScopedLogger(Logger::instance(), __METHOD__ ." - ");
        $start_parse_time = time();

        // Create and run a parser.
        $entries = array();
        $cache_directory = get_temp_dir() . BIBCITE_CACHE_DIRECTORY;
        $logger->debug("Using cache directory: " . $cache_directory);
        $filename = tempnam($cache_directory, __METHOD__);
        try {

            // Parsing a string seems to be much slower than parsing a file, so 
            // we write the string to a temporary file before parsing it.
            $logger->debug("Parsing temporary Bibtex file: $filename...");
            $handle = fopen($filename, "w");
            fwrite($handle, $str);
            fclose($handle);

            // Go!
            $parser = new \RenanBr\BibTexParser\Parser();
            $listener = new \RenanBr\BibTexParser\Listener();
            $parser->addListener($listener);
            $parser->parseFile($filename);
            $entries = $listener->export();

        } catch (\Exception $e) {
            $logger->error("Failed to parse string: " . $e->getMessage() . ".");
        } finally {
            unlink($filename);
        }

        // Log and return results
        $parse_duration = time() - $start_parse_time;
        $entry_count = sizeof($entries);
        $logger->debug("Parsed ${entry_count} entries in ${parse_duration}s.");

        return $entries;
    }
}

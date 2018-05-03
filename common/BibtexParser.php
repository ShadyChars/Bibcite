<?php

namespace Bibcite\Common;

require plugin_dir_path(dirname(__FILE__)) . 'vendor/autoload.php';

/**
 * Parses a local Bibtex file and returns a list of parsed entries.
 *
 * @author Keith Houston <keith@shadycharacters.co.uk>
 * @link https://github.com/OrkneyDullard/Bibcite
 * @package Bibcite\Common
 * @since 1.0.0
 */
class BibtexParser
{
    /**
     * Parse a local file into an array of strings containing individual Bibtex 
     * entries.
     *
     * @param string $filename
     * @return array an array of associative arrays, where each associative 
     * array is a parsed Bibtex entry. The Bibtex string itself is placed in an 
     * '_original' item.
     */
    public static function parse_file_to_bibtex(string $filename): array
    {
        $logger = new ScopedLogger(Logger::instance(), __METHOD__ ." - ");
        $start_parse_time = time();

        // Create and run a parser.
        $entries = array();
        try {
            $parser = new \RenanBr\BibTexParser\Parser();
            $listener = new \RenanBr\BibTexParser\Listener();
            $parser->addListener($listener);
            $parser->parseFile($filename);
            $entries = $listener->export();
        } catch (Exception $e) {
            $logger->error(
                "Failed to parse file (${filename}): " . $e->getMessage() . "."
            );
        }

        // Log and return results
        $parse_duration = time() - $start_parse_time;
        $entry_count = sizeof($entries);
        $logger->debug(
            "Parsed ${entry_count} entries from ${filename} in ${parse_duration}s."
        );

        return $entries;
    }
}

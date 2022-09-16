<?php declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use Bibcite\Common\BibtexParser;

require_once 'WordPressStubs.php'; 

final class BibtexParserTest extends TestCase
{
    public function testParsesRepresentativeEntries(): void
    {
        $library = file_get_contents(dirname(__FILE__) . "/library.bib");
        $entries = BibtexParser::parse_string_to_bibtex($library);
        $this->assertIsArray($entries);
        $this->assertNotEmpty($entries);
    }
}
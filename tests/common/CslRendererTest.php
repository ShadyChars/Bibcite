<?php declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use Bibcite\Common\BibtexParser;
use Bibcite\Common\CslRenderer;

require_once 'WordPressStubs.php';

final class CslRendererTest extends TestCase
{
    public function testRendersRepresentativeEntries(): void
    {
        $library = file_get_contents(dirname(__FILE__) . "/library.bib");
        $entries = BibtexParser::parse_string_to_bibtex($library);

        // Expect no exceptions
        $rendered_entries = CslRenderer::instance()->renderCslEntries(
            $entries, "chicago-fullnote-bibliography-with-url", "bibshow-definition-list"
        );

        $this->assertIsString($rendered_entries, "Expected 'rendered_entries' to be a string.");
        $this->assertNotEmpty($rendered_entries, "Expected 'rendered_entries' to be non-empty.");
        $this->assertFalse(
            str_contains($rendered_entries, "Error when rendering"), 
            "Expected 'rendered_entries' to be successfully rendered."
        );
        $this->assertTrue(
            str_contains($rendered_entries, "Tarantelli"), 
            "Expected 'rendered_entries' to contain previously problematic entry."
        );
    }
}
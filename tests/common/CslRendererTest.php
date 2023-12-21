<?php declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use Bibcite\Common\CslRenderer;

require_once 'WordPressStubs.php';

final class CslRendererTest extends TestCase
{
    public function testRendersRepresentativeEntries(): void
    {
        $library_json_string = file_get_contents(dirname(__FILE__) . "/library.json");
        $csl_entries = json_decode($library_json_string);

        // Expect no exceptions
        $rendered_entries = CslRenderer::instance()->renderCslEntries(
            $csl_entries, "chicago-fullnote-bibliography-with-url", "bibshow-definition-list"
        );

        $this->assertIsString($rendered_entries, "Expected 'rendered_entries' to be a string.");

        file_put_contents("D:\\Users\\keith\\Desktop\\rendered-library.html", $rendered_entries);

        $this->assertNotEmpty($rendered_entries, "Expected 'rendered_entries' to be non-empty.");
        $this->assertFalse(
            str_contains($rendered_entries, "Exception when rendering"), 
            "Expected 'rendered_entries' to be rendered without any exceptions."
        );
        $this->assertFalse(
            str_contains($rendered_entries, "Error when rendering"), 
            "Expected 'rendered_entries' to be rendered without any errors."
        );
        $this->assertTrue(
            str_contains($rendered_entries, "Tarantelli"), 
            "Expected 'rendered_entries' to contain previously problematic entry author."
        );
        $this->assertTrue(
            str_contains($rendered_entries, "Voice Into Text"), 
            "Expected 'rendered_entries' to contain previously problematic entry title."
        );
    }
}
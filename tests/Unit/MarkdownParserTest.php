<?php

namespace coderstape\Press\Tests;

use PHPUnit\Framework\Attributes\Test;
use coderstape\Press\MarkdownParser;

class MarkdownParserTest extends TestCase
{
    protected function tearDown(): void
    {
        // Static seam: a leak here changes every later test's ingest.
        // Same discipline as ImaginShortcodeTest.
        MarkdownParser::$renderer = null;

        parent::tearDown();
    }

    #[Test]
    public function the_default_path_renders_through_parsedown()
    {
        $this->assertEquals('<h1>Title</h1>', MarkdownParser::parse('# Title'));
    }

    #[Test]
    public function an_injected_renderer_replaces_parsedown_and_receives_the_raw_source()
    {
        $seen = [];

        MarkdownParser::$renderer = function ($text) use (&$seen) {
            $seen[] = $text;

            return 'STANDIN';
        };

        $this->assertEquals('STANDIN', MarkdownParser::parse('# Title'));

        // The seam is handed the RAW markdown, which is what lets
        // press:parser-diff categorize by source without any driver
        // reaching back to the file or column it came from.
        $this->assertEquals(['# Title'], $seen);

        // Null restores the default path -- the finally block in
        // press:parser-diff depends on exactly this.
        MarkdownParser::$renderer = null;

        $this->assertEquals('<h1>Title</h1>', MarkdownParser::parse('# Title'));
    }
}

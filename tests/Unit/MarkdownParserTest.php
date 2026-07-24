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
    public function commonmark_is_the_default_parser()
    {
        // The TestCase env sets no 'parser' key, so this exercises the
        // inline default. '#NoSpace' is the discriminator: Parsedown
        // accepts it as a heading, CommonMark requires the space and
        // leaves a paragraph with the hash visible.
        $this->assertEquals('<h1>Title</h1>', trim(MarkdownParser::parse('# Title')));
        $this->assertStringContainsString('<p>#NoSpace', MarkdownParser::parse('#NoSpace'));
    }

    #[Test]
    public function parsedown_can_still_be_selected_by_config()
    {
        // Kept selectable because an un-migrated blog still runs it and
        // press:parser-diff needs it as the comparison baseline.
        config(['press.parser' => 'parsedown']);

        $this->assertEquals('<h1>NoSpace</h1>', trim(MarkdownParser::parse('#NoSpace')));
    }

    #[Test]
    public function the_imagin_directive_survives_ingest_as_literal_escaped_text()
    {
        // THE pin the whole render-time expansion design rests on. The
        // directive must reach the stored body as literal text, wrapped
        // in <p>, with '=>' entity-escaped -- ImaginShortcode's regex
        // depends on both facts. Verified against the real corpus too:
        // 467 directives across 105 posts render identically under both
        // parsers.
        $body = MarkdownParser::parse("@imagin('location' => 'hero', 'width' => '3000')");

        $this->assertStringContainsString('<p>@imagin(', $body);
        $this->assertStringContainsString('=&gt;', $body);
        $this->assertStringNotContainsString('=>', strip_tags($body));
    }

    #[Test]
    public function bare_urls_and_email_addresses_are_autolinked()
    {
        // Deliberate choice, not a default. Parsedown autolinks only
        // protocol-prefixed URLs; the Autolink extension also catches
        // bare 'www.' hosts and email addresses. Without the extension
        // at all, 16 posts in the real corpus lost links outright.
        $body = MarkdownParser::parse(
            'See https://example.com and www.example.org or mail sales@example.com today.'
        );

        $this->assertStringContainsString('href="https://example.com"', $body);
        $this->assertStringContainsString('href="http://www.example.org"', $body);
        $this->assertStringContainsString('href="mailto:sales@example.com"', $body);
    }

    #[Test]
    public function an_injected_renderer_replaces_the_configured_parser()
    {
        $seen = [];

        MarkdownParser::$renderer = function ($text) use (&$seen) {
            $seen[] = $text;

            return 'STANDIN';
        };

        $this->assertEquals('STANDIN', MarkdownParser::parse('# Title'));

        // The seam is handed the RAW markdown, which is what lets
        // press:parser-diff categorize by source.
        $this->assertEquals(['# Title'], $seen);

        // Null restores the configured parser -- the finally block in
        // press:parser-diff depends on exactly this.
        MarkdownParser::$renderer = null;

        $this->assertEquals('<h1>Title</h1>', trim(MarkdownParser::parse('# Title')));
    }
}

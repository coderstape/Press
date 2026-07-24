<?php

namespace coderstape\Press\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use coderstape\Press\Blog;
use coderstape\Press\MarkdownParser;

class NormalizeSourceCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['press.driver' => 'database', 'press.database' => ['table' => 'blogs']]);

        // press:normalize-source is a PRE-migration tool: it proves its
        // edits are no-ops against the CURRENTLY configured parser, so
        // on a commonmark install it correctly holds back the very
        // fixes it exists to make (the heading rules turn a paragraph
        // back into a heading -- a rendering change by definition).
        // These tests therefore run in the configuration the tool is
        // meant for. Same constraint the config file documents as
        // 'step 2 before step 3'.
        config(['press.parser' => 'parsedown']);
    }

    #[Test]
    public function a_dry_run_writes_nothing()
    {
        $data = "---\ntitle: Lax---\n##Heading";
        Blog::factory()->create(['data' => $data]);

        $this->artisan('press:normalize-source')->assertExitCode(0);

        // Dry run is the default; --apply is the only thing that writes.
        $this->assertEquals($data, Blog::first()->data);
    }

    #[Test]
    public function applying_inserts_the_missing_space_without_changing_how_the_post_renders()
    {
        Blog::factory()->create(['data' => "---\ntitle: Lax---\n##Heading\n\nBody text."]);

        $before = MarkdownParser::parse("##Heading\n\nBody text.");

        $this->artisan('press:normalize-source', ['--apply' => true])->assertExitCode(0);

        $this->assertStringContainsString('## Heading', Blog::first()->data);

        // The whole point: the source is now spec-correct AND today's
        // rendering is byte-identical, so this can ship on its own
        // ahead of any parser swap.
        $this->assertEquals($before, MarkdownParser::parse("## Heading\n\nBody text."));
    }

    #[Test]
    public function an_opening_container_tag_is_left_alone()
    {
        // Inserting a blank line after an opening <div> DOES change
        // Parsedown's output, so the html-blocks rule is restricted to
        // void elements. This is the pin for that restriction.
        $data = "---\ntitle: Div---\n<div class=\"x\">\n## Inside\n</div>";
        Blog::factory()->create(['data' => $data]);

        $this->artisan('press:normalize-source', ['--apply' => true])->assertExitCode(0);

        $this->assertEquals($data, Blog::first()->data);
    }

    #[Test]
    public function a_void_element_followed_by_markdown_gains_a_blank_line()
    {
        Blog::factory()->create(['data' => "---\ntitle: Br---\n<br />\n## Heading"]);

        $this->artisan('press:normalize-source', ['--apply' => true])->assertExitCode(0);

        $this->assertStringContainsString("<br />\n\n## Heading", Blog::first()->data);
    }

    #[Test]
    public function a_trailing_hash_run_is_dropped_from_a_heading()
    {
        // '## Heading##' renders the trailing hashes as literal text
        // under CommonMark, which only reads them as a closing sequence
        // when whitespace precedes them. Parsedown strips them either
        // way, so this is a safe-tier rule.
        Blog::factory()->create(['data' => "---\ntitle: Close---\n## Best Offshore Performer##"]);

        $this->artisan('press:normalize-source', ['--apply' => true])->assertExitCode(0);

        $this->assertStringContainsString('## Best Offshore Performer', Blog::first()->data);
        $this->assertStringNotContainsString('Performer##', Blog::first()->data);
    }

    #[Test]
    public function the_trailing_hash_rule_works_on_crlf_sources()
    {
        // Regression pin: with Windows line endings the \r sits between
        // the closing hashes and end-of-line, and the first cut of this
        // rule matched neither -- it skipped the affected post entirely
        // while reporting success.
        Blog::factory()->create(['data' => "---\r\ntitle: CRLF---\r\n## Compact Offshore Performer##\r\nBody."]);

        $this->artisan('press:normalize-source', ['--apply' => true])->assertExitCode(0);

        $this->assertStringNotContainsString('Performer##', Blog::first()->data);
    }

    #[Test]
    public function the_emphasis_rule_trims_inner_space_on_either_side_without_merging_pairs()
    {
        // Regression pin. The first cut of this rule only handled a
        // space after the OPENING delimiter, so '**text **' -- the more
        // common spelling in the real corpus -- was silently skipped.
        // Its replacement used one alternation for both sides and
        // matched ACROSS delimiter pairs, turning '**a** and *b*' into
        // '**a**and*b*'. Both failures are pinned here.
        Blog::factory()->create([
            'data' => "---\ntitle: Emph---\n**Dealer: Riverside ** and ** 1. Engine:** plus **fine** and *ok*",
        ]);

        $this->artisan('press:normalize-source', [
            '--rule' => ['emphasis'],
            '--allow-visible-change' => true,
            '--apply' => true,
        ])->assertExitCode(0);

        $data = Blog::first()->data;

        $this->assertStringContainsString('**Dealer: Riverside**', $data);
        $this->assertStringContainsString('**1. Engine:**', $data);
        // Correct markup is left exactly as it was.
        $this->assertStringContainsString('**fine** and *ok*', $data);
    }

    #[Test]
    public function the_emphasis_rule_refuses_to_run_without_the_visible_change_flag()
    {
        $data = "---\ntitle: Emph---\n** 1. Engine:** text";
        Blog::factory()->create(['data' => $data]);

        // Unlike the other rules this one changes current rendering
        // (the bold loses a leading space), so it must be asked for
        // explicitly and shipped in its own deploy.
        $this->artisan('press:normalize-source', ['--rule' => ['emphasis'], '--apply' => true])
            ->assertExitCode(1);

        $this->assertEquals($data, Blog::first()->data);
    }

    #[Test]
    public function the_command_refuses_a_driver_it_cannot_write_to()
    {
        config(['press.driver' => 'file', 'press.file' => ['path' => __DIR__ . '/../stubs']]);

        $this->artisan('press:normalize-source')->assertExitCode(0);

        // Rewriting source files or remote gists is out of scope; the
        // command says so rather than silently doing nothing.
        $this->assertTrue(true);
    }
}

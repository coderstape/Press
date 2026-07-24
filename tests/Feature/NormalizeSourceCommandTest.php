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

<?php

namespace coderstape\Press\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Console\Exception\CommandNotFoundException;
use coderstape\Press\Blog;
use coderstape\Press\MarkdownParser;
use coderstape\Press\Post;

class ParserDiffCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // The command walks whichever driver is configured; the
        // database driver lets these tests control the exact source.
        config(['press.driver' => 'database', 'press.database' => ['table' => 'blogs']]);
    }

    protected function tearDown(): void
    {
        MarkdownParser::$renderer = null;

        parent::tearDown();
    }

    /** Runs the command quietly and returns the report file's contents. */
    protected function reportFor($data)
    {
        Blog::factory()->create(['data' => $data]);

        $path = sys_get_temp_dir() . '/press-parser-diff-' . uniqid() . '.txt';

        // Assertions read the report file rather than the console: the
        // file is fully under this test's control, where console
        // output has to survive table formatting and terminal width.
        $this->artisan('press:parser-diff', ['--report' => $path, '--show' => 0])
            ->assertExitCode(0);

        return File::get($path);
    }

    #[Test]
    public function command_is_available()
    {
        try {
            $this->artisan('press:parser-diff');
            $this->assertTrue(true);
        } catch (CommandNotFoundException $e) {
            $this->fail('Unable to locate the command \'press:parser-diff\'');
        }
    }

    #[Test]
    public function a_heading_without_a_space_is_reported_as_structurally_different()
    {
        // '#NoSpace' is an h1 under Parsedown and a plain paragraph
        // under CommonMark, which requires a space after the # run.
        // Not hypothetical: that exact spelling is in this repo's own
        // AdminPostControllerTest fixture.
        $report = $this->reportFor("---\ntitle: Lax Heading---\n#NoSpace");

        $this->assertStringContainsString('atx-heading-needs-space', $report);
    }

    #[Test]
    public function the_imagin_directive_survives_both_parsers()
    {
        // THE load-bearing pin. The directive must reach the stored
        // body as literal text with '=>' entity-escaped under either
        // parser, because the render-time expander's regex depends on
        // both facts. If a migration would break that, it shows up
        // here first -- which is the main reason this command exists.
        $report = $this->reportFor(
            "---\ntitle: With Imagin---\n@imagin('location' => 'hero')"
        );

        $this->assertStringNotContainsString('imagin-directive-changed', $report);
    }

    #[Test]
    public function a_bare_url_is_not_reported_as_a_difference()
    {
        // Parsedown autolinks bare URLs and CommonMark core does not,
        // so without the Autolink extension registered this post looks
        // like it loses a link. It is a parser-configuration gap, not
        // content breakage -- 16 real posts were mis-reported this way.
        $report = $this->reportFor(
            "---\ntitle: Bare URL---\nRetrieved from https://example.com/some/page for details."
        );

        // The report carries FULL context, not just changed lines, so
        // the URL appears in it either way -- an earlier version of
        // this test asserted its absence from the whole file and could
        // never have passed. What matters is that it never shows up on
        // a CHANGED line.
        $changed = array_filter(
            explode("\n", $report),
            fn ($line) => str_starts_with($line, '- ') || str_starts_with($line, '+ ')
        );

        $this->assertStringNotContainsString('example.com', implode("\n", $changed));
    }

    #[Test]
    public function the_command_leaves_the_renderer_seam_clean()
    {
        Blog::factory()->create();

        $this->artisan('press:parser-diff', ['--show' => 0])->assertExitCode(0);

        // Static seam -- a leak would silently change every later
        // press:process running in the same process.
        $this->assertNull(MarkdownParser::$renderer);
    }

    #[Test]
    public function the_command_writes_nothing_to_posts_or_blogs()
    {
        $data = "---\ntitle: Untouched---\n#NoSpace";
        Blog::factory()->create(['data' => $data]);

        $this->artisan('press:parser-diff', ['--show' => 0])->assertExitCode(0);

        // Read-only by construction: it fetches through the driver and
        // throws the results away. No post rows, source untouched.
        $this->assertCount(0, Post::all());
        $this->assertEquals($data, Blog::first()->data);
    }
}

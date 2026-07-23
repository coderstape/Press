<?php

namespace coderstape\Press\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use coderstape\Press\ImaginShortcode;
use coderstape\Press\Post;

class ImaginShortcodeTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        ImaginShortcode::$renderer = null;

        parent::tearDown();
    }

    /** Captures params and returns a recognizable stand-in tag. */
    protected function fakeRenderer(&$captured)
    {
        return function (array $params) use (&$captured) {
            $captured[] = $params;

            return "<div data-rendered='" . $params['location'] . "'></div>";
        };
    }

    #[Test]
    public function a_stored_directive_is_expanded_at_render_time()
    {
        // Exactly what Parsedown stores at ingest: the directive as
        // literal paragraph text, => escaped to =&gt; (irregular
        // spacing included -- real bodies have it).
        $body = "<p>Intro paragraph.</p>\n"
            . "<p>@imagin('location' =&gt; 'blog-680-image-1', 'width' =&gt;  '3000', 'height' =&gt; '2000', 'class' =&gt; 'w-full')</p>\n"
            . "<p>Following paragraph.</p>";

        $captured = [];
        $expanded = ImaginShortcode::expand($body, $this->fakeRenderer($captured));

        $this->assertStringContainsString("<div data-rendered='blog-680-image-1'></div>", $expanded);
        $this->assertStringNotContainsString('@imagin', $expanded);
        $this->assertStringNotContainsString('=&gt;', $expanded);

        // The entity-escaped expression parsed into clean params.
        $this->assertEquals([
            'location' => 'blog-680-image-1',
            'width' => '3000',
            'height' => '2000',
            'class' => 'w-full',
        ], $captured[0]);

        // Surrounding content is untouched.
        $this->assertStringContainsString('<p>Intro paragraph.</p>', $expanded);
        $this->assertStringContainsString('<p>Following paragraph.</p>', $expanded);
    }

    #[Test]
    public function the_wrapping_paragraph_is_unwrapped()
    {
        // Parsedown wraps the directive in <p>. Imagin's empty-location
        // placeholder is a <div>, which may not sit inside a paragraph
        // -- browsers force-close the <p> and shatter the layout -- so
        // a sole-content wrapper is consumed along with the directive.
        $body = "<p>@imagin('location' =&gt; 'solo')</p>";

        $captured = [];
        $expanded = ImaginShortcode::expand($body, $this->fakeRenderer($captured));

        $this->assertEquals("<div data-rendered='solo'></div>", $expanded);
        $this->assertStringNotContainsString('<p>', $expanded);
    }

    #[Test]
    public function unescaped_arrows_and_inline_directives_also_expand()
    {
        // Bodies stored by a different pipeline (or hand-edited) may
        // carry a raw =>, and a directive may sit mid-paragraph; an
        // inline expansion keeps its surrounding <p> (only sole-content
        // wrappers are unwrapped).
        $body = "<p>Before @imagin('location' => 'inline-loc') after.</p>";

        $captured = [];
        $expanded = ImaginShortcode::expand($body, $this->fakeRenderer($captured));

        $this->assertEquals(
            "<p>Before <div data-rendered='inline-loc'></div> after.</p>",
            $expanded
        );
    }

    #[Test]
    public function values_may_contain_parentheses()
    {
        $body = "<p>@imagin('location' =&gt; 'x', 'alt' =&gt; 'The Open (402) at speed')</p>";

        $captured = [];
        ImaginShortcode::expand($body, $this->fakeRenderer($captured));

        $this->assertEquals('The Open (402) at speed', $captured[0]['alt']);
    }

    #[Test]
    public function non_literal_expressions_are_left_untouched_and_never_evaluated()
    {
        // Runtime variables and arbitrary code are Blade-view features;
        // inside a stored body there is nothing to resolve them against,
        // and evaluating author-controlled content is out of the
        // question. Visible literal text beats silent loss or execution.
        $bodies = [
            "<p>@imagin('location' =&gt; \$slug, 'width' =&gt; '100')</p>",
            "<p>@imagin(exec('rm -rf /'))</p>",
            "<p>@imagin('width' =&gt; '100')</p>", // no location
        ];

        foreach ($bodies as $body) {
            $captured = [];
            $expanded = ImaginShortcode::expand($body, $this->fakeRenderer($captured));

            $this->assertEquals($body, $expanded);
            $this->assertEmpty($captured);
        }
    }

    #[Test]
    public function unknown_keys_never_reach_the_renderer()
    {
        // Attribute values are escaped when Imagin spreads them into
        // markup, but attribute NAMES are not -- and here the names come
        // from author-controlled blog content calling Imagin::image()
        // directly, which has no whitelist of its own. This whitelist is
        // the attribute-name-injection defense; this test is the
        // tripwire for anyone loosening it.
        $body = "<p>@imagin('location' =&gt; 'x', 'onerror' =&gt; 'alert(1)', 'data-evil' =&gt; 'y', 'class' =&gt; 'ok')</p>";

        $captured = [];
        ImaginShortcode::expand($body, $this->fakeRenderer($captured));

        $this->assertEquals(['location' => 'x', 'class' => 'ok'], $captured[0]);
    }

    #[Test]
    public function multiple_directives_expand_independently()
    {
        $body = "<p>@imagin('location' =&gt; 'one')</p>\n"
            . "<p>Text between.</p>\n"
            . "<p>@imagin('location' =&gt; 'two')</p>";

        $captured = [];
        $expanded = ImaginShortcode::expand($body, $this->fakeRenderer($captured));

        $this->assertCount(2, $captured);
        $this->assertStringContainsString("data-rendered='one'", $expanded);
        $this->assertStringContainsString("data-rendered='two'", $expanded);
    }

    #[Test]
    public function bodies_pass_through_unchanged_when_no_renderer_is_available()
    {
        // Press does not depend on Imagin. Without the facade and
        // without an injected renderer, expansion is a no-op.
        $body = "<p>@imagin('location' =&gt; 'x')</p>";

        $this->assertEquals($body, ImaginShortcode::expand($body));
    }

    #[Test]
    public function the_post_body_accessor_expands_directives()
    {
        ImaginShortcode::$renderer = function (array $params) {
            return "<div data-rendered='" . $params['location'] . "'></div>";
        };

        $post = Post::factory()->create([
            'body' => "<p>@imagin('location' =&gt; 'accessor-loc')</p>",
        ]);

        $this->assertEquals(
            "<div data-rendered='accessor-loc'></div>",
            $post->fresh()->body
        );
    }
}

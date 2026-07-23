<?php

namespace coderstape\Press\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;
use coderstape\Press\ImaginShortcode;
use coderstape\Press\Post;

/**
 * End-to-end thread: authored markdown -> press:process ingest ->
 * public HTTP render, with an @imagin directive surviving ingest as
 * literal text and expanding only at render time through the
 * injected renderer -- the full invariant behind the Imagin
 * integration design.
 */
class IngestRenderTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        ImaginShortcode::$renderer = null;

        parent::tearDown();
    }

    #[Test]
    public function a_markdown_file_is_ingested_and_rendered_with_imagin_expansion()
    {
        $dir = sys_get_temp_dir() . '/press-e2e-' . uniqid();
        File::ensureDirectoryExists($dir);
        File::put($dir . '/post.md', implode("\n", [
            '---',
            'title: End to End',
            'date: May 14 1988',
            'tags: E2E',
            'series: Threads',
            '---',
            'Intro paragraph.',
            '',
            "@imagin('location' => 'e2e-loc')",
            '',
            'Outro paragraph.',
        ]));
        config(['press.file' => ['path' => $dir], 'press.blog' => []]);

        $this->artisan('press:process');

        $post = Post::first();

        // Stored body keeps the directive as Parsedown literal text...
        $this->assertStringContainsString('@imagin', $post->getRawOriginal('body'));

        // ...and the public page expands it through the renderer hook.
        ImaginShortcode::$renderer = function (array $params) {
            return "<div data-rendered='" . $params['location'] . "'></div>";
        };

        $response = $this->get($post->path());

        $response->assertOk();
        $response->assertSee('Intro paragraph.');
        $response->assertSee("data-rendered='e2e-loc'", false);
        $this->assertStringNotContainsString('@imagin', $response->content());
    }
}

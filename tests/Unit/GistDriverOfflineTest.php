<?php

namespace coderstape\Press\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use coderstape\Press\Drivers\GistDriver;

/**
 * Offline counterpart to GistDriverTest (which stays in the excluded
 * 'integration' group because it calls the live GitHub API). These
 * fake the HTTP layer and pin the driver's parsing contract.
 *
 * Gist sources are two-level: the configured gist contains a
 * newline-separated LIST of gist ids, and each of those gists holds
 * one post's markdown.
 */
class GistDriverOfflineTest extends TestCase
{
    use RefreshDatabase;

    protected function fakeGists(array $map)
    {
        $responses = [];
        foreach ($map as $id => $payload) {
            $responses['api.github.com/gists/' . $id] = Http::response($payload);
        }
        Http::fake($responses);
    }

    protected function gistWithContent($content, $filename = 'file.md')
    {
        return ['files' => [$filename => ['content' => $content]]];
    }

    #[Test]
    public function it_fetches_posts_through_a_string_source()
    {
        config(['press.driver' => 'gist', 'press.gist' => ['source' => 'src1']]);
        $this->fakeGists([
            'src1' => $this->gistWithContent("post1"),
            'post1' => $this->gistWithContent("---\ntitle: Gist Post---\nBody"),
        ]);

        $posts = (new GistDriver())->fetchPosts();

        $this->assertCount(1, $posts);
        $this->assertEquals('Gist Post', $posts[0]['title']);
    }

    #[Test]
    public function it_accepts_an_array_of_sources_and_multi_line_lists()
    {
        config(['press.driver' => 'gist', 'press.gist' => ['source' => ['srcA', 'srcB']]]);
        $this->fakeGists([
            'srcA' => $this->gistWithContent("post1\npost2"),
            'srcB' => $this->gistWithContent("post3"),
            'post1' => $this->gistWithContent("---\ntitle: One---\nBody"),
            'post2' => $this->gistWithContent("---\ntitle: Two---\nBody"),
            'post3' => $this->gistWithContent("---\ntitle: Three---\nBody"),
        ]);

        $posts = (new GistDriver())->fetchPosts();

        $this->assertCount(3, $posts);
        $this->assertEquals(['One', 'Two', 'Three'], array_column($posts, 'title'));
    }

    #[Test]
    public function identifiers_are_the_slugged_gist_ids()
    {
        config(['press.driver' => 'gist', 'press.gist' => ['source' => 'src1']]);
        $this->fakeGists([
            'src1' => $this->gistWithContent("Post_ID_1"),
            'Post_ID_1' => $this->gistWithContent("---\ntitle: One---\nBody"),
        ]);

        $posts = (new GistDriver())->fetchPosts();

        $this->assertEquals('post-id-1', $posts[0]['identifier']);
    }

    #[Test]
    public function an_api_error_response_skips_that_source_entirely()
    {
        // The GitHub API signals errors with a 'message' key.
        config(['press.driver' => 'gist', 'press.gist' => ['source' => ['bad', 'good']]]);
        $this->fakeGists([
            'bad' => ['message' => 'Not Found'],
            'good' => $this->gistWithContent("post1"),
            'post1' => $this->gistWithContent("---\ntitle: Survivor---\nBody"),
        ]);

        $posts = (new GistDriver())->fetchPosts();

        $this->assertCount(1, $posts);
        $this->assertEquals('Survivor', $posts[0]['title']);
    }

    #[Test]
    public function nothing_valid_returns_null()
    {
        config(['press.driver' => 'gist', 'press.gist' => ['source' => 'bad']]);
        $this->fakeGists(['bad' => ['message' => 'Not Found']]);

        $this->assertNull((new GistDriver())->fetchPosts());
    }

    #[Test]
    public function a_multi_file_gist_uses_its_last_file()
    {
        // array_pop takes the LAST entry of the API's files map --
        // arbitrary but long-standing; pinned so a refactor to "first
        // file" is a conscious contract change, not an accident.
        config(['press.driver' => 'gist', 'press.gist' => ['source' => 'src1']]);
        $this->fakeGists([
            'src1' => ['files' => [
                'a.txt' => ['content' => 'ignored-post'],
                'b.txt' => ['content' => 'post1'],
            ]],
            'post1' => $this->gistWithContent("---\ntitle: From B---\nBody"),
        ]);

        $posts = (new GistDriver())->fetchPosts();

        $this->assertCount(1, $posts);
        $this->assertEquals('From B', $posts[0]['title']);
    }
}

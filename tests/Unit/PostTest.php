<?php

namespace coderstape\Press\Tests;

use Illuminate\Auth\GenericUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use coderstape\Press\AIContent;
use coderstape\Press\Author;
use coderstape\Press\Blog;
use coderstape\Press\Facades\Press;
use coderstape\Press\Post;
use coderstape\Press\Series;
use coderstape\Press\Tag;
use coderstape\Press\Trending;

class PostTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_builds_its_path_from_the_configured_press_path()
    {
        $post = Post::factory()->create(['slug' => 'my-post']);

        $this->assertEquals(
            "http://localhost/blog/{$post->id}-my-post",
            $post->path()
        );
    }

    #[Test]
    public function extra_reads_a_field_from_the_json_column_and_null_when_missing()
    {
        $post = Post::factory()->create();

        // Factory extra is ['test' => 'value', 'author' => 'Test Author'].
        $this->assertEquals('value', $post->extra('test'));
        $this->assertNull($post->extra('missing'));
    }

    #[Test]
    public function image_prefers_the_extra_img_and_falls_back_to_the_blog_config()
    {
        config(['press.blog.image' => 'config-fallback.png']);

        $withImg = Post::factory()->create([
            'extra' => json_encode(['img' => 'from-extra.png']),
        ]);
        $withoutImg = Post::factory()->create();

        $this->assertEquals('from-extra.png', $withImg->image());
        $this->assertEquals('config-fallback.png', $withoutImg->image());
    }

    #[Test]
    public function record_visit_creates_a_trending_row_for_the_post()
    {
        $post = Post::factory()->create();

        $post->recordVisit();

        $this->assertCount(1, Trending::all());
        $this->assertEquals($post->id, Trending::first()->post_id);
        $this->assertCount(1, $post->visits);
    }

    #[Test]
    public function the_active_scope_hides_inactive_posts_from_guests()
    {
        Post::factory()->create(['active' => 1]);
        Post::factory()->create(['active' => 0]);

        $this->assertCount(1, Post::active()->get());
        $this->assertCount(2, Post::all());
    }

    #[Test]
    public function the_active_scope_shows_everything_to_a_registered_editor()
    {
        Post::factory()->create(['active' => 1]);
        Post::factory()->create(['active' => 0]);

        Press::editors(['editor@example.com']);
        $this->actingAs(new GenericUser(['id' => 1, 'email' => 'editor@example.com']));

        $this->assertCount(2, Post::active()->get());
    }

    #[Test]
    public function it_belongs_to_a_series()
    {
        $series = Series::factory()->create();
        $post = Post::factory()->create(['series_id' => $series->id]);

        $this->assertTrue($post->series->is($series));
    }

    #[Test]
    public function it_belongs_to_an_author()
    {
        $author = Author::factory()->create();
        $post = Post::factory()->create(['author_id' => $author->id]);

        $this->assertTrue($post->author->is($author));
    }

    #[Test]
    public function it_belongs_to_many_tags()
    {
        $post = Post::factory()->create();
        $tag = Tag::factory()->create();

        $post->tags()->attach($tag);

        $this->assertTrue($post->tags->first()->is($tag));
    }

    #[Test]
    public function it_reaches_its_raw_blog_record_through_the_identifier_column()
    {
        // Database-driver posts store the Blog row's id (slugged) as
        // their identifier; blog() resolves it back.
        $blog = Blog::factory()->create();
        $post = Post::factory()->create(['identifier' => (string) $blog->id]);

        $this->assertTrue($post->blog->is($blog));
    }

    /**
     * ★ THE REGRESSION GUARD FOR THE $appends BUG. Until v1.0.1 Post
     * carried `protected $appends = ['author', 'contentable']` with no
     * getAuthorAttribute() / getContentableAttribute() behind it, so
     * every one of these three calls threw BadMethodCallException. Put
     * that property back and this test fails at the first line of the
     * try block -- which is the only reason it is worth having.
     */
    #[Test]
    public function a_post_serializes_without_blowing_up_on_a_phantom_appended_key()
    {
        $post = Post::factory()->create(['slug' => 'serialize-me']);

        $array = $post->toArray();
        $encoded = json_encode($post);
        $json = $post->toJson();

        $this->assertIsArray($array);
        $this->assertIsString($encoded);
        $this->assertSame(JSON_ERROR_NONE, json_last_error());
        $this->assertEquals($array, json_decode($json, true));

        // The real columns are all still there.
        $this->assertSame('serialize-me', $array['slug']);
        $this->assertArrayHasKey('title', $array);
        $this->assertArrayHasKey('published_at', $array);

        // And the two accessorless keys are gone rather than fatal. An
        // unloaded relation is simply absent from Eloquent's output.
        $this->assertArrayNotHasKey('author', $array);
        $this->assertArrayNotHasKey('contentable', $array);
    }

    /**
     * Removing them from $appends costs the serialized output nothing
     * that was ever actually reachable: an eager-loaded relation
     * serializes on its own, which is the path PostController already
     * takes with ->with(['tags', 'author']).
     */
    #[Test]
    public function eager_loaded_relations_still_reach_the_serialized_output()
    {
        $author = Author::factory()->create(['name' => 'Ada Lovelace']);
        $post = Post::factory()->create(['author_id' => $author->id]);
        $post->contentable()->create(['data' => ['first takeaway']]);

        $array = Post::with(['author', 'contentable'])->find($post->id)->toArray();

        $this->assertSame('Ada Lovelace', $array['author']['name']);
        $this->assertSame(['first takeaway'], $array['contentable']['data']);
    }
}

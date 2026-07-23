<?php

namespace coderstape\Press\Tests;

use Illuminate\Auth\GenericUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
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
}

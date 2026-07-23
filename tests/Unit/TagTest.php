<?php

namespace coderstape\Press\Tests;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use coderstape\Press\Post;
use coderstape\Press\Tag;

class TagTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_builds_its_path()
    {
        $tag = Tag::factory()->create(['slug' => 'laravel']);

        $this->assertEquals(
            "http://localhost/blog/tags/{$tag->id}-laravel",
            $tag->path()
        );
    }

    #[Test]
    public function it_belongs_to_many_posts()
    {
        $tag = Tag::factory()->create();
        $post = Post::factory()->create();
        $tag->posts()->attach($post);

        $this->assertTrue($tag->posts->first()->is($post));
    }

    #[Test]
    public function active_posts_filters_inactive_and_orders_newest_first()
    {
        $tag = Tag::factory()->create();
        $older = Post::factory()->create(['published_at' => Carbon::now()->subDay()]);
        $newer = Post::factory()->create(['published_at' => Carbon::now()]);
        $inactive = Post::factory()->create(['active' => 0]);
        $tag->posts()->attach([$older->id, $newer->id, $inactive->id]);

        $activePosts = $tag->activePosts;

        $this->assertCount(2, $activePosts);
        $this->assertTrue($activePosts->first()->is($newer));
    }
}

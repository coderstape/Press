<?php

namespace coderstape\Press\Tests;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use coderstape\Press\Author;
use coderstape\Press\Post;

class AuthorTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_builds_its_path_by_slugging_its_name_on_the_fly()
    {
        // Authors have no slug column; path() slugs the name each call.
        $author = Author::factory()->create(['name' => 'John Doe']);

        $this->assertEquals(
            "http://localhost/blog/authors/{$author->id}-john-doe",
            $author->path()
        );
    }

    #[Test]
    public function it_has_many_posts()
    {
        $author = Author::factory()->create();
        Post::factory()->count(2)->create(['author_id' => $author->id]);
        Post::factory()->create();

        $this->assertCount(2, $author->posts);
    }

    #[Test]
    public function active_posts_filters_inactive_and_orders_newest_first()
    {
        $author = Author::factory()->create();
        $older = Post::factory()->create([
            'author_id' => $author->id,
            'published_at' => Carbon::now()->subDay(),
        ]);
        $newer = Post::factory()->create([
            'author_id' => $author->id,
            'published_at' => Carbon::now(),
        ]);
        Post::factory()->create(['author_id' => $author->id, 'active' => 0]);

        $activePosts = $author->activePosts;

        $this->assertCount(2, $activePosts);
        $this->assertTrue($activePosts->first()->is($newer));
        $this->assertTrue($activePosts->last()->is($older));
    }
}

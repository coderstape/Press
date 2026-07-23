<?php

namespace coderstape\Press\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use coderstape\Press\Press;
use coderstape\Press\Post;
use coderstape\Press\Trending;

class TrendingTest extends TestCase
{
    use RefreshDatabase;
    
    #[Test]
    public function a_visit_gets_recorded_when_a_post_is_visited()
    {
        $post = Post::factory()->create();

        $this->get($post->path());

        $trendings = Trending::all();

        $this->assertCount(1, $trendings);
        // post_id, not id: the old pin compared the trending row's own
        // id and only passed because both happened to be 1.
        $this->assertEquals($post->id, $trendings->first()->post_id);
    }
    
    #[Test]
    public function trendings_posts_can_be_fetched()
    {
        Trending::factory()->create();

        $trending = press::trending();

        $this->assertEquals(Post::first()->id, $trending->first()->post_id);
    }
    
    #[Test]
    public function trendings_can_be_limited()
    {
        Trending::factory()->count(100)->create();

        $trending = press::trending(10);

        $this->assertCount(10, $trending);
    }

    #[Test]
    public function a_trending_row_belongs_to_its_post()
    {
        $trending = Trending::factory()->create();

        $this->assertTrue($trending->post->is(Post::first()));
    }

    #[Test]
    public function trending_excludes_posts_that_are_no_longer_active()
    {
        Trending::factory()->create();
        Trending::factory()->create([
            'post_id' => Post::factory()->create(['active' => 0])->id,
        ]);

        $trending = press::trending();

        $this->assertCount(1, $trending);
        $this->assertEquals(1, $trending->first()->post->active);
    }
}

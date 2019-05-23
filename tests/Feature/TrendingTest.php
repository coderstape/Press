<?php

namespace coderstape\Press\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use coderstape\Press\Press;
use coderstape\Press\Post;
use coderstape\Press\Trending;

class TrendingTest extends TestCase
{
    use RefreshDatabase;
    
    /** @test */
    public function a_visit_gets_recorded_when_a_post_is_visited()
    {
        $post = factory(Post::class)->create();

        $this->get($post->path());

        $trendings = Trending::all();

        $this->assertCount(1, $trendings);
        $this->assertEquals($post->id, $trendings->first()->id);
    }
    
    /** @test */
    public function trendings_posts_can_be_fetched()
    {
        factory(Trending::class)->create();

        $trending = press::trending();

        $this->assertEquals(Post::first()->id, $trending->first()->post_id);
    }
    
    /** @test */
    public function trendings_can_be_limited()
    {
        factory(Trending::class, 100)->create();

        $trending = press::trending(10);

        $this->assertCount(10, $trending);
    }
}

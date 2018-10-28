<?php

namespace vicgonvt\LaraPress\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use vicgonvt\LaraPress\Post;
use vicgonvt\LaraPress\Trending;

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
}

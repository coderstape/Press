<?php

namespace coderstape\Press\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use coderstape\Press\Post;
use coderstape\Press\Series;

class SeriesTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_builds_its_path()
    {
        $series = Series::factory()->create(['slug' => 'adventure']);

        $this->assertEquals(
            "http://localhost/blog/series/{$series->id}-adventure",
            $series->path()
        );
    }

    #[Test]
    public function slug_finds_a_series_by_slugging_the_given_string()
    {
        $series = Series::factory()->create(['title' => 'Adventure Time', 'slug' => 'adventure-time']);

        $this->assertTrue(Series::slug('Adventure Time')->is($series));
        $this->assertTrue(Series::slug('aDvEnTuRe TiMe')->is($series));
        $this->assertNull(Series::slug('Unknown'));
    }

    #[Test]
    public function it_has_many_posts_and_active_posts_filters_inactive()
    {
        $series = Series::factory()->create();
        Post::factory()->create(['series_id' => $series->id]);
        Post::factory()->create(['series_id' => $series->id, 'active' => 0]);

        $this->assertCount(2, $series->posts);
        $this->assertCount(1, $series->activePosts);
    }
}

<?php

namespace coderstape\Press\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use coderstape\Press\Post;
use coderstape\Press\Series;

class SeriesControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_index_lists_only_series_that_have_active_posts()
    {
        $used = Series::factory()->create(['title' => 'Used Series']);
        Post::factory()->create(['series_id' => $used->id]);
        $draftOnly = Series::factory()->create(['title' => 'Draft Series']);
        Post::factory()->create(['series_id' => $draftOnly->id, 'active' => 0]);
        Series::factory()->create(['title' => 'Empty Series']);

        $response = $this->get('/blog/series');

        $response->assertOk();
        $this->assertEquals(['Used Series'], $response->viewData('series')->pluck('title')->all());
    }

    #[Test]
    public function showing_a_series_renders_it_and_sets_meta()
    {
        config(['press.blog' => []]);
        $series = Series::factory()->create(['title' => 'Epic Saga']);
        Post::factory()->create(['series_id' => $series->id]);

        $response = $this->get($series->path());

        $response->assertOk();
        $this->assertEquals('Epic Saga', app('Press')->meta('title'));
    }

    #[Test]
    public function a_wrong_series_slug_and_a_series_without_active_posts_are_404s()
    {
        $series = Series::factory()->create(['slug' => 'right']);
        Post::factory()->create(['series_id' => $series->id]);
        $empty = Series::factory()->create();

        $this->get("/blog/series/{$series->id}-wrong")->assertNotFound();
        $this->get($empty->path())->assertNotFound();
    }
}

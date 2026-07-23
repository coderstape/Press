<?php

namespace coderstape\Press\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use coderstape\Press\Post;
use coderstape\Press\Tag;

class TagControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_index_lists_only_tags_that_have_active_posts()
    {
        $used = Tag::factory()->create(['name' => 'Used Tag']);
        $used->posts()->attach(Post::factory()->create());
        $draftOnly = Tag::factory()->create(['name' => 'Draft Only']);
        $draftOnly->posts()->attach(Post::factory()->create(['active' => 0]));
        Tag::factory()->create(['name' => 'Unused Tag']);

        $response = $this->get('/blog/tags');

        $response->assertOk();
        $this->assertEquals(['Used Tag'], $response->viewData('tags')->pluck('name')->all());
    }

    #[Test]
    public function showing_a_tag_paginates_its_active_posts_and_sets_meta()
    {
        config(['press.pagination' => 2, 'press.blog' => []]);
        $tag = Tag::factory()->create(['name' => 'Boats']);
        $tag->posts()->attach(Post::factory()->count(3)->create()->pluck('id'));
        $tag->posts()->attach(Post::factory()->create(['active' => 0]));

        $response = $this->get($tag->path());

        $response->assertOk();
        $this->assertCount(2, $response->viewData('posts'));
        $this->assertEquals(3, $response->viewData('posts')->total());
        $this->assertEquals('Boats', app('Press')->meta('title'));
    }

    #[Test]
    public function a_wrong_tag_slug_is_a_404()
    {
        $tag = Tag::factory()->create(['slug' => 'right']);

        $this->get("/blog/tags/{$tag->id}-wrong")->assertNotFound();
    }
}

<?php

namespace coderstape\Press\Tests;

use Carbon\Carbon;
use Illuminate\Auth\GenericUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use coderstape\Press\Author;
use coderstape\Press\Facades\Press;
use coderstape\Press\Post;
use coderstape\Press\Tag;
use coderstape\Press\Trending;

class PostControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // The views read press.blog meta through app('Press').
        config(['press.blog' => config('press.blog', []) + ['title' => 'Test Blog']]);
    }

    #[Test]
    public function the_index_lists_active_posts_newest_first()
    {
        $older = Post::factory()->create(['published_at' => Carbon::now()->subDay()]);
        $newer = Post::factory()->create(['published_at' => Carbon::now()]);
        $draft = Post::factory()->create(['active' => 0]);

        $response = $this->get('/blog');

        $response->assertOk();
        $response->assertViewIs('press::.posts.index');
        $this->assertEquals(
            [$newer->id, $older->id],
            $response->viewData('posts')->pluck('id')->all()
        );
        $response->assertDontSee($draft->title);
    }

    #[Test]
    public function the_index_paginates_by_the_configured_size()
    {
        config(['press.pagination' => 2]);
        Post::factory()->count(3)->create();

        $response = $this->get('/blog');

        $this->assertCount(2, $response->viewData('posts'));
        $this->assertEquals(3, $response->viewData('posts')->total());
    }

    #[Test]
    public function search_matches_title_body_and_author_name()
    {
        $author = Author::factory()->create(['name' => 'Findable Author']);
        $byTitle = Post::factory()->create(['title' => 'Needle in the title']);
        $byBody = Post::factory()->create(['body' => 'a needle in the body']);
        $byAuthor = Post::factory()->create(['author_id' => $author->id]);
        Post::factory()->create(['title' => 'Unrelated']);

        $this->assertEquals(
            [$byTitle->id, $byBody->id],
            $this->get('/blog?search=needle')->viewData('posts')->pluck('id')->sort()->values()->all()
        );
        $this->assertEquals(
            [$byAuthor->id],
            $this->get('/blog?search=Findable')->viewData('posts')->pluck('id')->all()
        );
    }

    #[Test]
    public function search_does_not_leak_inactive_posts()
    {
        // Regression pin: the ungrouped orWhere chain used to escape
        // the active() constraint, publishing drafts whose body
        // matched the search.
        Post::factory()->create(['title' => 'Active needle']);
        $draft = Post::factory()->create(['title' => 'Draft needle', 'active' => 0]);

        $results = $this->get('/blog?search=needle')->viewData('posts');

        $this->assertCount(1, $results);
        $this->assertNotContains($draft->id, $results->pluck('id'));
    }

    #[Test]
    public function the_draft_filter_shows_editors_only_their_drafts()
    {
        Post::factory()->create(['active' => 1]);
        $draft = Post::factory()->create(['active' => 0]);

        Press::editors(['editor@example.com']);
        $this->actingAs(new GenericUser(['id' => 1, 'email' => 'editor@example.com']));

        $results = $this->get('/blog?draft=1')->viewData('posts');

        $this->assertEquals([$draft->id], $results->pluck('id')->all());
    }

    #[Test]
    public function showing_a_post_renders_it_and_records_a_visit()
    {
        $post = Post::factory()->create(['title' => 'Visible Post']);

        $response = $this->get($post->path());

        $response->assertOk();
        $response->assertSee('Visible Post');
        $this->assertCount(1, Trending::all());
    }

    #[Test]
    public function a_wrong_slug_is_a_404()
    {
        $post = Post::factory()->create(['slug' => 'right-slug']);

        $this->get("/blog/{$post->id}-wrong-slug")->assertNotFound();
    }

    #[Test]
    public function an_inactive_post_is_a_404_for_guests_but_renders_with_the_preview_param()
    {
        // Pins CURRENT behavior: ?preview is not gated -- anyone with
        // the URL sees the draft. May be intentional (shareable
        // preview links) or an oversight; gating it behind isEditor()
        // is an open roadmap decision, not a test fix.
        $post = Post::factory()->create(['active' => 0, 'slug' => 'hidden']);

        $this->get($post->path())->assertNotFound();
        $this->get($post->path() . '?preview=1')->assertOk();
    }

    #[Test]
    public function related_posts_share_at_least_one_tag()
    {
        $tag = Tag::factory()->create();
        $post = Post::factory()->create();
        $related = Post::factory()->create();
        $unrelated = Post::factory()->create();
        $post->tags()->attach($tag);
        $related->tags()->attach($tag);

        $response = $this->get($post->path());

        $this->assertEquals([$related->id], $response->viewData('related')->pluck('id')->all());
    }

    #[Test]
    public function a_post_without_tags_still_renders()
    {
        // Regression pin: $related used to be undefined for tag-less
        // posts, handing compact() an unset variable.
        $post = Post::factory()->create();

        $response = $this->get($post->path());

        $response->assertOk();
        $this->assertCount(0, $response->viewData('related'));
    }
}

<?php

namespace coderstape\Press\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use coderstape\Press\Author;
use coderstape\Press\Post;

class AuthorControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * press.blog must exist BEFORE the app boots: route registration
     * resolves the Press singleton (via Press::path()), whose
     * constructor caches config('press.blog') into its meta right
     * then. A config() call inside a test runs after boot and never
     * reaches the already-built singleton -- which is why the
     * original in-test config produced '' here while a real site
     * (config published before boot) shows the blog default.
     */
    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('press.blog', ['title' => 'Default Title']);
    }

    #[Test]
    public function the_index_lists_only_authors_that_have_active_posts()
    {
        // Also the regression pin for the authors index view, which
        // was a copy of the series index iterating an undefined
        // $series variable -- the page had never rendered.
        $active = Author::factory()->create(['name' => 'Active Author']);
        Post::factory()->create(['author_id' => $active->id]);
        $draftOnly = Author::factory()->create(['name' => 'Draft Author']);
        Post::factory()->create(['author_id' => $draftOnly->id, 'active' => 0]);

        $response = $this->get('/blog/authors');

        $response->assertOk();
        $response->assertSee('Active Author');
        $response->assertDontSee('Draft Author');
    }

    #[Test]
    public function showing_an_author_renders_and_sets_default_meta()
    {
        // There is no Transformers\Author, so meta() quietly keeps the
        // blog defaults on author pages (pinned in PressTest; a real
        // author transformer is a roadmap item).
        $author = Author::factory()->create(['name' => 'John Doe']);
        Post::factory()->create(['author_id' => $author->id]);

        $response = $this->get($author->path());

        $response->assertOk();
        $response->assertSee('John Doe');
        $this->assertEquals('Default Title', app('Press')->meta('title'));
    }

    #[Test]
    public function an_author_without_active_posts_is_a_404_but_a_wrong_slug_is_not()
    {
        // show() checks the id and active-posts constraint but NOT the
        // slug (unlike tags/series, authors have no slug column).
        // Pinned as current behavior; a canonical-slug redirect would
        // be a roadmap item, not a test fix.
        $hidden = Author::factory()->create();
        $visible = Author::factory()->create(['name' => 'John Doe']);
        Post::factory()->create(['author_id' => $visible->id]);

        $this->get($hidden->path())->assertNotFound();
        $this->get("/blog/authors/{$visible->id}-anything-goes")->assertOk();
    }
}

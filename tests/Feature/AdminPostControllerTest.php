<?php

namespace coderstape\Press\Tests;

use Illuminate\Auth\GenericUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use PHPUnit\Framework\Attributes\Test;
use coderstape\Press\Blog;
use coderstape\Press\Facades\Press;
use coderstape\Press\Post;

class AdminPostControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Admin flows author into the blogs table and reprocess it.
        config(['press.driver' => 'database', 'press.database' => ['table' => 'blogs']]);

        // The package ships NO admin views (see the briefing: the
        // consuming site publishes its own), so the render tests
        // provide minimal ones through the theme mechanism.
        $base = sys_get_temp_dir() . '/press-admin-views';
        File::ensureDirectoryExists($base . '/testtheme/admin/posts');
        File::put($base . '/testtheme/admin/posts/index.blade.php', 'admin index: {{ $posts->count() }}');
        File::put($base . '/testtheme/admin/posts/create.blade.php', 'admin create');
        File::put($base . '/testtheme/admin/posts/edit.blade.php', 'admin edit: {{ $post->id }}');
        View::addLocation($base);
        config(['press.theme' => 'testtheme']);
    }

    protected function actingAsAdmin()
    {
        // Authoring is editor-gated now, so the acting user has to be
        // ON the list -- being authenticated stopped being enough.
        Press::editors(['admin@example.com']);

        return $this->actingAs(new GenericUser(['id' => 1, 'email' => 'admin@example.com']));
    }

    #[Test]
    public function guests_are_redirected_to_login()
    {
        Route::get('login', function () {
            return 'login';
        })->name('login');

        // Required for routes named AFTER boot: the collection's
        // name-lookup table is built when a route is add()ed, and the
        // fluent ->name() lands after that. Boot-time routes get a
        // framework refreshNameLookups() pass; mid-test routes don't,
        // so route('login') in the auth middleware would still throw
        // RouteNotFoundException without this.
        Route::getRoutes()->refreshNameLookups();

        $this->get('/blog/admin/posts')->assertRedirect('http://localhost/login');
    }

    #[Test]
    public function the_index_and_create_pages_render_for_editors()
    {
        Post::factory()->count(2)->create();

        $this->actingAsAdmin();

        $this->get('/blog/admin/posts')->assertOk()->assertSee('admin index: 2');
        $this->get('/blog/admin/posts/create')->assertOk()->assertSee('admin create');
    }

    #[Test]
    public function the_admin_gate_accepts_an_editor_listed_only_in_the_config()
    {
        // The refactor's whole point, end to end: a site can list its
        // authors in config/press.php and never call Press::editors()
        // at all. Note actingAsAdmin() is deliberately NOT used here.
        config(['press.authorized' => ['config-only@example.com']]);

        $this->actingAs(new GenericUser(['id' => 4, 'email' => 'config-only@example.com']));

        $this->get('/blog/admin/posts')->assertOk();
    }

    #[Test]
    public function authenticated_non_editors_are_forbidden_from_every_admin_route()
    {
        // The gate 'auth' alone never provided. Every verb is asserted
        // rather than one representative route: the gate is registered
        // once in the constructor today, and this is what would catch
        // a missed route if it ever moves to per-route middleware.
        Press::editors(['editor@example.com']);
        $blog = Blog::factory()->create();

        $this->actingAs(new GenericUser(['id' => 2, 'email' => 'nobody@example.com']));

        $this->get('/blog/admin/posts')->assertForbidden();
        $this->get('/blog/admin/posts/create')->assertForbidden();
        $this->post('/blog/admin/posts', ['data' => 'x'])->assertForbidden();
        $this->get("/blog/admin/posts/{$blog->id}/edit")->assertForbidden();
        $this->patch("/blog/admin/posts/{$blog->id}", ['data' => 'x'])->assertForbidden();

        // Nothing was authored on the way through.
        $this->assertCount(1, Blog::all());
    }

    #[Test]
    public function storing_requires_data()
    {
        $this->actingAsAdmin();

        $this->post('/blog/admin/posts', [])->assertSessionHasErrors('data');
        $this->assertCount(0, Blog::all());
    }

    #[Test]
    public function storing_creates_the_blog_processes_it_and_redirects_to_edit()
    {
        $this->actingAsAdmin();

        $response = $this->post('/blog/admin/posts', [
            'data' => "---\ntitle: Admin Authored---\nAdmin body",
        ]);

        $blog = Blog::first();
        $response->assertRedirect("http://localhost/blog/admin/posts/{$blog->id}/edit");
        $this->assertEquals('Admin Authored', Post::first()->title);
        // The derived post ties back to its raw source by blog id.
        $this->assertEquals((string) $blog->id, Post::first()->identifier);
    }

    #[Test]
    public function storing_returns_error_when_the_driver_yields_no_posts()
    {
        // Point the file driver at an empty directory: the Blog row is
        // created but process() finds nothing to ingest.
        $empty = sys_get_temp_dir() . '/press-empty-' . uniqid();
        File::ensureDirectoryExists($empty);
        config(['press.driver' => 'file', 'press.file' => ['path' => $empty]]);

        $this->actingAsAdmin();

        $response = $this->post('/blog/admin/posts', ['data' => 'anything']);

        $response->assertOk();
        $this->assertEquals('error', $response->content());
        $this->assertCount(1, Blog::all());
    }

    #[Test]
    public function the_edit_page_renders_the_raw_blog_and_missing_blogs_are_404()
    {
        $blog = Blog::factory()->create();

        $this->actingAsAdmin();

        $this->get("/blog/admin/posts/{$blog->id}/edit")->assertOk()->assertSee("admin edit: {$blog->id}");
        $this->get('/blog/admin/posts/999/edit')->assertNotFound();
    }

    #[Test]
    public function updating_the_raw_blog_regenerates_the_derived_post()
    {
        // posts.body is DERIVED from Blog.data -- editing the source
        // reparses and rewrites the post (the invariant the Imagin
        // render-time expansion design depends on).
        $this->actingAsAdmin();
        $this->post('/blog/admin/posts', ['data' => "---\ntitle: First Version---\nOld body"]);
        $blog = Blog::first();

        $response = $this->patch("/blog/admin/posts/{$blog->id}", [
            'data' => "---\ntitle: Second Version---\n# New Heading",
        ]);

        $response->assertRedirect();
        $this->assertCount(1, Post::all());
        $this->assertEquals('Second Version', Post::first()->title);
        $this->assertEquals('<h1>New Heading</h1>', Post::first()->body);
    }

    #[Test]
    public function updating_requires_data()
    {
        $blog = Blog::factory()->create();

        $this->actingAsAdmin();

        $this->patch("/blog/admin/posts/{$blog->id}", [])->assertSessionHasErrors('data');
    }
}
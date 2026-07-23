<?php

namespace coderstape\Press\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use coderstape\Press\Post;

class CustomPathTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Routes read Press::path() at registration, so the custom path
     * must be in place before the app boots -- hence the env override
     * rather than config() inside the test.
     */
    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('press.path', '/news');
    }

    #[Test]
    public function the_blog_serves_from_a_customized_path()
    {
        $post = Post::factory()->create(['slug' => 'moved']);

        $this->assertStringStartsWith('http://localhost/news/', $post->path());
        $this->get($post->path())->assertOk();
        $this->get('/news')->assertOk();
        $this->get('/blog')->assertNotFound();
    }
}

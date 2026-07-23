<?php

namespace coderstape\Press\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use coderstape\Press\Blog;
use coderstape\Press\Post;

class BlogTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_builds_its_admin_path()
    {
        $blog = Blog::factory()->create();

        $this->assertEquals(
            "http://localhost/blog/admin/posts/{$blog->id}",
            $blog->path()
        );
    }

    #[Test]
    public function it_reaches_its_derived_post_through_the_identifier_column()
    {
        $blog = Blog::factory()->create();
        $post = Post::factory()->create(['identifier' => (string) $blog->id]);

        $this->assertTrue($blog->post->is($post));
    }
}

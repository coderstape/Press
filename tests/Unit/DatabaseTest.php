<?php

namespace vicgonvt\LaraPress\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use vicgonvt\LaraPress\Actions\Database;
use vicgonvt\LaraPress\Post;
use vicgonvt\LaraPress\PressFileParser;
use vicgonvt\LaraPress\Series;

class DatabaseTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_post_can_be_created()
    {
        $post = (new PressFileParser(__DIR__ . '/../stubs/MarkFile1.md'))
            ->getData();

        $db = (new Database())->savePosts(
            [array_merge($post, ['identifier' => 'test'])]
        );

        $this->assertCount(1, Post::all());
    }

    /** @test */
    public function a_series_gets_added_and_associated()
    {
        $post = (new PressFileParser(__DIR__ . '/../stubs/MarkFile1.md'))
            ->getData();

        $db = (new Database())->savePosts(
            [array_merge($post, ['identifier' => 'test'])]
        );

        $this->assertCount(1, Series::all());
        $this->assertEquals('my-first-post', Post::first()->series->slug);
    }

    /** @test */
    public function a_post_is_updated_and_not_duplicated()
    {
        $post = (new PressFileParser(__DIR__ . '/../stubs/MarkFile1.md'))
            ->getData();
        $posts = [
            array_merge($post, ['identifier' => 'test']),
            array_merge($post, ['identifier' => 'test']),
        ];

        $db = (new Database())->savePosts($posts);

        $this->assertCount(1, Post::all());
    }
    
    /** @test */
    public function a_post_gets_deactivated_if_not_present()
    {
        $post = (new PressFileParser(__DIR__ . '/../stubs/MarkFile1.md'))
            ->getData();

        $db = (new Database())->savePosts(
            [array_merge($post, ['identifier' => 'test'])]
        );

        $this->assertCount(1, Post::active()->get());

        $db = (new Database())->savePosts([]);
        $this->assertCount(0, Post::active()->get());
    }

    /** @test */
    public function series_are_removed_if_no_longer_used()
    {
        $post = (new PressFileParser(__DIR__ . '/../stubs/MarkFile1.md'))
            ->getData();

        $db = (new Database())->savePosts(
            [array_merge($post, ['identifier' => 'test'])]
        );

        $this->assertCount(1, Series::all());

        $db = (new Database())->savePosts([]);
        $this->assertCount(0, Series::get());
    }
}
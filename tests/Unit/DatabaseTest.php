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
}
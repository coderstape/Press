<?php

namespace coderstape\Press\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use coderstape\Press\Actions\Database;
use coderstape\Press\Post;
use coderstape\Press\PressFileParser;
use coderstape\Press\Series;
use coderstape\Press\Tag;

class DatabaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_post_can_be_created()
    {
        $post = (new PressFileParser(__DIR__ . '/../stubs/MarkFile1.md'))
            ->getData();

        (new Database())->savePosts(
            [array_merge($post, ['identifier' => 'test'])]
        );

        $this->assertCount(1, Post::all());
    }

    public function test_a_post_is_updated_and_not_duplicated()
    {
        $post = (new PressFileParser(__DIR__ . '/../stubs/MarkFile1.md'))
            ->getData();
        $posts = [
            array_merge($post, ['identifier' => 'test']),
            array_merge($post, ['identifier' => 'test']),
        ];

        (new Database())->savePosts($posts);

        $this->assertCount(1, Post::all());
    }
    
    public function test_a_post_gets_deactivated_if_not_present()
    {
        $post = (new PressFileParser(__DIR__ . '/../stubs/MarkFile1.md'))
            ->getData();

        (new Database())->savePosts(
            [array_merge($post, ['identifier' => 'test'])]
        );

        $this->assertCount(1, Post::active()->get());

        (new Database())->savePosts([]);
        $this->assertCount(0, Post::active()->get());
    }

    public function test_a_series_gets_added_and_associated()
    {
        $post = (new PressFileParser(__DIR__ . '/../stubs/MarkFile1.md'))
            ->getData();

        (new Database())->savePosts(
            [array_merge($post, ['identifier' => 'test'])]
        );

        $this->assertCount(1, Series::all());
        $this->assertEquals('my-first-post', Post::first()->series->slug);
    }

    public function test_series_are_removed_if_no_longer_used()
    {
        $post = (new PressFileParser(__DIR__ . '/../stubs/MarkFile1.md'))
            ->getData();

        (new Database())->savePosts(
            [array_merge($post, ['identifier' => 'test'])]
        );

        $this->assertCount(1, Series::all());

        (new Database())->savePosts([]);
        $this->assertCount(0, Series::all());
    }
    
    public function test_a_post_without_a_series_can_still_be_added()
    {
        $post = (new PressFileParser(__DIR__ . '/../stubs/MarkFile2.md'))
            ->getData();

        (new Database())->savePosts(
            [array_merge($post, ['identifier' => 'test'])]
        );

        $this->assertCount(1, Post::all());
    }

    public function test_tags_get_added_and_associated()
    {
        $post = (new PressFileParser(__DIR__ . '/../stubs/MarkFile1.md'))
            ->getData();

        (new Database())->savePosts(
            [array_merge($post, ['identifier' => 'test'])]
        );

        $this->assertCount(2, Tag::all());
        $this->assertCount(1, Tag::first()->posts);
        $this->assertCount(2, Post::first()->tags);
    }
    
    public function test_tags_dont_get_duplicated()
    {
        $post1 = (new PressFileParser(__DIR__ . '/../stubs/MarkFile1.md'))
            ->getData();
        $post2 = (new PressFileParser(__DIR__ . '/../stubs/MarkFile2.md'))
            ->getData();

        $this->assertCount(3, Tag::all());
    }

    public function test_tags_are_removed_if_no_longer_used()
    {
        $post = (new PressFileParser(__DIR__ . '/../stubs/MarkFile1.md'))
            ->getData();

        (new Database())->savePosts(
            [array_merge($post, ['identifier' => 'test'])]
        );

        $this->assertCount(2, Tag::all());

        Post::truncate();
        (new Database())->savePosts([]);
        $this->assertCount(0, Tag::all()->fresh());
    }
}
<?php

namespace coderstape\Press\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use coderstape\Press\Actions\Database;
use coderstape\Press\Post;
use coderstape\Press\PressFileParser;
use coderstape\Press\Series;
use coderstape\Press\Tag;

class DatabaseTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function a_post_can_be_created()
    {
        $post = (new PressFileParser(__DIR__ . '/../stubs/MarkFile1.md'))
            ->getData();

        (new Database())->savePosts(
            [array_merge($post, ['identifier' => 'test'])]
        );

        $this->assertCount(1, Post::all());
    }

    #[Test]
    public function a_post_is_updated_and_not_duplicated()
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
    
    #[Test]
    public function a_post_gets_deactivated_if_not_present()
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

    #[Test]
    public function a_series_gets_added_and_associated()
    {
        $post = (new PressFileParser(__DIR__ . '/../stubs/MarkFile1.md'))
            ->getData();

        (new Database())->savePosts(
            [array_merge($post, ['identifier' => 'test'])]
        );

        $this->assertCount(1, Series::all());
        $this->assertEquals('my-first-post', Post::first()->series->slug);
    }

    #[Test]
    public function series_are_removed_if_no_longer_used()
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
    
    #[Test]
    public function a_post_without_a_series_can_still_be_added()
    {
        $post = (new PressFileParser(__DIR__ . '/../stubs/MarkFile2.md'))
            ->getData();

        (new Database())->savePosts(
            [array_merge($post, ['identifier' => 'test'])]
        );

        $this->assertCount(1, Post::all());
    }

    #[Test]
    public function tags_get_added_and_associated()
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
    
    #[Test]
    public function tags_dont_get_duplicated()
    {
        $post1 = (new PressFileParser(__DIR__ . '/../stubs/MarkFile1.md'))
            ->getData();
        $post2 = (new PressFileParser(__DIR__ . '/../stubs/MarkFile2.md'))
            ->getData();

        $this->assertCount(3, Tag::all());
    }

    #[Test]
    public function tags_are_removed_if_no_longer_used()
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

    #[Test]
    public function a_capitalized_series_head_survives_the_cleanup_pass()
    {
        // Regression pin: cleanSeries used to pluck the lowercase
        // 'series' key only, so 'Series:'-authored posts had their
        // series deleted as "unused" in the same savePosts run.
        $post = (new PressFileParser("---\nTitle: Cap\nSeries: Kept Series---\nBody"))
            ->getData();

        (new Database())->savePosts(
            [array_merge($post, ['identifier' => 'cap'])]
        );

        $this->assertCount(1, Series::all());
        $this->assertEquals('Kept Series', Post::first()->series->title);
    }

    #[Test]
    public function a_minimal_post_with_only_a_title_and_body_can_be_ingested()
    {
        // Regression pin: savePost hard-indexed active/tag_ids/etc.,
        // so a post authored without a date or tags head crashed
        // ingest. Defaults: publish now, no tags.
        $post = (new PressFileParser("---\nTitle: Bare Minimum---\nBody"))->getData();

        (new Database())->savePosts(
            [array_merge($post, ['identifier' => 'bare'])]
        );

        $saved = Post::first();

        $this->assertEquals('Bare Minimum', $saved->title);
        $this->assertEquals(1, $saved->active);
        $this->assertNotNull($saved->published_at);
        $this->assertCount(0, $saved->tags);
    }
}
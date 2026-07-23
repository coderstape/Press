<?php

namespace coderstape\Press\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use coderstape\Press\Post;
use coderstape\Press\Series;
use coderstape\Press\Tag;
use coderstape\Press\Transformers\Post as PostTransformer;
use coderstape\Press\Transformers\Series as SeriesTransformer;
use coderstape\Press\Transformers\Tag as TagTransformer;

class TransformersTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_post_transformer_maps_meta_from_the_extra_column()
    {
        $post = Post::factory()->create([
            'title' => 'A Title',
            'extra' => json_encode([
                'description' => 'A description',
                'keywords' => 'one, two',
                'img' => 'img/path.png',
            ]),
        ]);

        $this->assertEquals([
            'title' => 'A Title',
            'description' => 'A description',
            'keywords' => 'one, two',
            'image' => 'img/path.png',
            'url' => $post->path(),
        ], (new PostTransformer)->transform($post));
    }

    #[Test]
    public function the_series_transformer_derives_description_and_keywords_from_the_title()
    {
        $series = Series::factory()->create(['title' => 'Epic Saga']);

        $this->assertEquals([
            'title' => 'Epic Saga',
            'description' => 'Showing all posts in the series titled Epic Saga',
            'keywords' => 'Epic, Saga',
            'url' => $series->path(),
        ], (new SeriesTransformer)->transform($series));
    }

    #[Test]
    public function the_tag_transformer_derives_description_and_keywords_from_the_name()
    {
        $tag = Tag::factory()->create(['name' => 'Boat Care']);

        $this->assertEquals([
            'title' => 'Boat Care',
            'description' => 'Now showing only posts that are associated and filtered by the tag Boat Care',
            'keywords' => 'Boat, Care',
            'url' => $tag->path(),
        ], (new TagTransformer)->transform($tag));
    }
}

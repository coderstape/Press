<?php

namespace coderstape\Press\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use coderstape\Press\Press;
use coderstape\Press\Post;
use coderstape\Press\Series;
use coderstape\Press\Tag;

class PressTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_can_store_meta_information()
    {
        config(['press.blog' => [
            'field1' => 'test1',
            'field2' => 'test2',
        ]]);

        $press = new Press();

        $this->assertEquals('test1', $press->meta('field1'));
        $this->assertEquals('test2', $press->meta('field2'));
    }

    public function test_it_can_set_a_parameter_with_an_array()
    {
        config(['press.blog' => [
            'field1' => 'test1',
            'field2' => 'test2',
        ]]);

        $press = new Press();
        $press->meta(['field3' => 'test3']);

        $this->assertEquals('test3', $press->meta('field3'));
    }

    public function test_it_can_overwrite_an_existing_field()
    {
        config(['press.blog' => [
            'field1' => 'test1',
            'field2' => 'test2',
        ]]);

        $press = new Press();
        $press->meta(['field1' => 'new value']);

        $this->assertEquals('new value', $press->meta('field1'));
    }

    public function test_it_can_parse_a_post_model_and_override_meta_tags()
    {
        $post = factory(Post::class)->create();

        $press = new Press();
        $press->meta($post);

        $this->assertEquals($post->title, $press->meta('title'));
        $this->assertEquals($post->extra('description'), $press->meta('description'));
        $this->assertEquals($post->extra('keywords'), $press->meta('keywords'));
        $this->assertEquals($post->extra('img'), $press->meta('image'));
        $this->assertEquals($post->path(), $press->meta('url'));
    }

    public function test_it_can_parse_a_tag_and_override_meta_tags()
    {
        $tag = factory(Tag::class)->create();

        $press = new Press();
        $press->meta($tag);

        $this->assertEquals($tag->name, $press->meta('title'));
        $this->assertEquals(
            'Now showing only posts that are associated and filtered by the tag ' . $tag->name,
            $press->meta('description')
        );
        $this->assertEquals(str_replace(' ', ', ', $tag->name), $press->meta('keywords'));
        $this->assertEquals($tag->path(), $press->meta('url'));
    }

    public function test_it_can_parse_a_series_and_override_meta_tags()
    {
        $series = factory(Series::class)->create();

        $press = new Press();
        $press->meta($series);

        $this->assertEquals($series->title, $press->meta('title'));
        $this->assertEquals(
            'Showing all posts in the series titled ' . $series->title,
            $press->meta('description')
        );
        $this->assertEquals(str_replace(' ', ', ', $series->title), $press->meta('keywords'));
        $this->assertEquals($series->path(), $press->meta('url'));
    }
}

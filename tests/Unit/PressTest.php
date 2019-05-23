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

    /** @test */
    public function it_can_store_meta_information()
    {
        config(['press.blog' => [
            'field1' => 'test1',
            'field2' => 'test2',
        ]]);

        $laraPress = new Press();

        $this->assertEquals('test1', $laraPress->meta('field1'));
        $this->assertEquals('test2', $laraPress->meta('field2'));
    }

    /** @test */
    public function it_can_set_a_parameter_with_an_array()
    {
        config(['press.blog' => [
            'field1' => 'test1',
            'field2' => 'test2',
        ]]);

        $laraPress = new Press();
        $laraPress->meta(['field3' => 'test3']);

        $this->assertEquals('test3', $laraPress->meta('field3'));
    }
    
    /** @test */
    public function it_can_overwrite_an_existing_field()
    {
        config(['press.blog' => [
            'field1' => 'test1',
            'field2' => 'test2',
        ]]);

        $laraPress = new Press();
        $laraPress->meta(['field1' => 'new value']);

        $this->assertEquals('new value', $laraPress->meta('field1'));
    }

    /** @test */
    public function it_can_parse_a_post_model_and_override_meta_tags()
    {
        $post = factory(Post::class)->create();

        $laraPress = new Press();
        $laraPress->meta($post);

        $this->assertEquals($post->title, $laraPress->meta('title'));
        $this->assertEquals($post->extra('description'), $laraPress->meta('description'));
        $this->assertEquals($post->extra('keywords'), $laraPress->meta('keywords'));
        $this->assertEquals($post->extra('img'), $laraPress->meta('image'));
        $this->assertEquals($post->path(), $laraPress->meta('url'));
    }
    
    /** @test */
    public function it_can_parse_a_tag_and_override_meta_tags()
    {
        $tag = factory(Tag::class)->create();

        $laraPress = new Press();
        $laraPress->meta($tag);

        $this->assertEquals($tag->name, $laraPress->meta('title'));
        $this->assertEquals(
            'Showing all posts associated with the tag ' . $tag->name,
            $laraPress->meta('description')
        );
        $this->assertEquals(str_replace(' ', ', ', $tag->name), $laraPress->meta('keywords'));
        $this->assertEquals($tag->path(), $laraPress->meta('url'));
    }
    
    /** @test */
    public function it_can_parse_a_series_and_override_meta_tags()
    {
        $series = factory(Series::class)->create();

        $laraPress = new Press();
        $laraPress->meta($series);

        $this->assertEquals($series->title, $laraPress->meta('title'));
        $this->assertEquals(
            'Showing all posts in the series titled ' . $series->title,
            $laraPress->meta('description')
        );
        $this->assertEquals(str_replace(' ', ', ', $series->title), $laraPress->meta('keywords'));
        $this->assertEquals($series->path(), $laraPress->meta('url'));
    }
}
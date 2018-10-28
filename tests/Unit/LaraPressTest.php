<?php

namespace vicgonvt\LaraPress\Tests;

use vicgonvt\LaraPress\LaraPress;

class LaraPressTest extends TestCase
{
    /** @test */
    public function it_can_store_meta_information()
    {
        config(['larapress.blog' => [
            'field1' => 'test1',
            'field2' => 'test2',
        ]]);

        $laraPress = new LaraPress();

        $this->assertEquals('test1', $laraPress->meta('field1'));
        $this->assertEquals('test2', $laraPress->meta('field2'));
    }

    /** @test */
    public function it_can_set_a_parameter_with_an_array()
    {
        config(['larapress.blog' => [
            'field1' => 'test1',
            'field2' => 'test2',
        ]]);

        $laraPress = new LaraPress();
        $laraPress->meta(['field3' => 'test3']);

        $this->assertEquals('test3', $laraPress->meta('field3'));
    }
    
    /** @test */
    public function it_can_overwrite_an_existing_field()
    {
        config(['larapress.blog' => [
            'field1' => 'test1',
            'field2' => 'test2',
        ]]);

        $laraPress = new LaraPress();
        $laraPress->meta(['field1' => 'new value']);

        $this->assertEquals('new value', $laraPress->meta('field1'));
    }
}
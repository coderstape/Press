<?php

namespace vicgonvt\LaraPress\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\Console\Exception\CommandNotFoundException;
use vicgonvt\LaraPress\Post;

class ProcessCommandTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function command_is_available()
    {
        try {
            $this->artisan('larapress:process');
            $this->assertTrue(true);
        } catch (CommandNotFoundException $e) {
            $this->fail('Unable to locate the command \'larapress:process\'');
        }
    }
    
    /** @test */
    public function it_adds_the_stub_posts_to_db()
    {
        config(['larapress.file' => [
            'path' => __DIR__ . '/../stubs',
        ]]);

        $this->artisan('larapress:process');

        $this->assertCount(2, Post::all()->fresh());
    }
}

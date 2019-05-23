<?php

namespace coderstape\Press\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\Console\Exception\CommandNotFoundException;
use coderstape\Press\Post;

class ProcessCommandTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function command_is_available()
    {
        try {
            $this->artisan('press:process');
            $this->assertTrue(true);
        } catch (CommandNotFoundException $e) {
            $this->fail('Unable to locate the command \'press:process\'');
        }
    }
    
    /** @test */
    public function it_adds_the_stub_posts_to_db()
    {
        config(['press.file' => [
            'path' => __DIR__ . '/../stubs',
        ]]);

        $this->artisan('press:process');

        $this->assertCount(2, Post::all()->fresh());
    }
}

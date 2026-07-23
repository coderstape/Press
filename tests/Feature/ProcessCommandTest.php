<?php

namespace coderstape\Press\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Console\Exception\CommandNotFoundException;
use coderstape\Press\Post;

class ProcessCommandTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function command_is_available()
    {
        try {
            $this->artisan('press:process');
            $this->assertTrue(true);
        } catch (CommandNotFoundException $e) {
            $this->fail('Unable to locate the command \'press:process\'');
        }
    }
    
    #[Test]
    public function it_adds_the_stub_posts_to_db()
    {
        config(['press.file' => [
            'path' => __DIR__ . '/../stubs',
        ]]);

        $this->artisan('press:process');

        $this->assertCount(2, Post::all()->fresh());
    }

    #[Test]
    public function it_warns_when_the_config_is_not_published()
    {
        config(['press' => null]);

        $this->artisan('press:process')
            ->expectsOutput("Please publish the config file by running 'php artisan vendor:publish --tag=press-config'")
            ->assertExitCode(0);
    }

    #[Test]
    public function it_reports_success_after_processing()
    {
        config(['press.file' => [
            'path' => __DIR__ . '/../stubs',
        ]]);

        $this->artisan('press:process')
            ->expectsOutput('Press process complete.')
            ->assertExitCode(0);
    }

    #[Test]
    public function it_warns_when_the_driver_finds_no_posts()
    {
        $empty = sys_get_temp_dir() . '/press-empty-' . uniqid();
        File::ensureDirectoryExists($empty);
        config(['press.file' => ['path' => $empty]]);

        $this->artisan('press:process')
            ->expectsOutput('No posts were updated.')
            ->assertExitCode(0);
    }

    #[Test]
    public function driver_exceptions_are_reported_as_command_errors()
    {
        config(['press.driver' => 'database', 'press.database' => ['table' => 'fake_table']]);

        $this->artisan('press:process')
            ->expectsOutput("The 'fake_table' table was not found in your database. Run 'php artisan migrate' to create it.")
            ->assertExitCode(0);
    }
}

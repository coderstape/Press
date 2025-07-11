<?php

namespace coderstape\Press\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use coderstape\Press\Drivers\FileDriver;
use coderstape\Press\Exceptions\FileDriverDirectoryNotFoundException;

class FileDriverTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_throws_an_exception_if_file_directory_is_not_found()
    {
        config(['press.file' => [
            'path' => 'some/fake/path',
        ]]);

        $this->expectException(FileDriverDirectoryNotFoundException::class);

        new FileDriver();
    }

    public function test_file_driver_can_fetch_posts()
    {
        config(['press.file' => [
            'path' => __DIR__ . '/../stubs',
        ]]);

        $driver = new FileDriver();

        $this->assertCount(count(File::files(__DIR__ . '/../stubs')), $driver->fetchPosts());
    }
}
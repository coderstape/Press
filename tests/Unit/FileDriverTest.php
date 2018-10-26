<?php

namespace vicgonvt\LaraPress\Tests;

use Illuminate\Support\Facades\File;
use vicgonvt\LaraPress\Drivers\FileDriver;

class FileDriverTest extends TestCase
{
    /** @test */
    public function it_throws_an_exception_if_file_directory_is_not_found()
    {
        config(['larapress.file' => [
            'path' => 'some/fake/path',
        ]]);

        $this->expectException(\Exception::class);

        new FileDriver();
    }

    /** @test */
    public function file_driver_can_fetch_posts()
    {
        config(['larapress.file' => [
            'path' => __DIR__ . '/../stubs',
        ]]);

        $driver = new FileDriver();

        $this->assertCount(count(File::files(__DIR__ . '/../stubs')), $driver->fetchPosts());
    }
}
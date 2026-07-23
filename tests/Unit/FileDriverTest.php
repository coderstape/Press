<?php

namespace coderstape\Press\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;
use coderstape\Press\Drivers\FileDriver;
use coderstape\Press\Exceptions\FileDriverDirectoryNotFoundException;

class FileDriverTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_throws_an_exception_if_file_directory_is_not_found()
    {
        config(['press.file' => [
            'path' => 'some/fake/path',
        ]]);

        $this->expectException(FileDriverDirectoryNotFoundException::class);

        new FileDriver();
    }

    #[Test]
    public function file_driver_can_fetch_posts()
    {
        config(['press.file' => [
            'path' => __DIR__ . '/../stubs',
        ]]);

        $driver = new FileDriver();

        $this->assertCount(count(File::files(__DIR__ . '/../stubs')), $driver->fetchPosts());
    }

    #[Test]
    public function identifiers_are_the_slugged_filenames()
    {
        config(['press.file' => [
            'path' => __DIR__ . '/../stubs',
        ]]);

        $posts = (new FileDriver())->fetchPosts();

        // Str::slug('MarkFile1.md') -- the extension's dot is dropped.
        $this->assertEquals(['markfile1md', 'markfile2md'], array_column($posts, 'identifier'));
    }
}
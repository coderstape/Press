<?php

namespace vicgonvt\LaraPress\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use vicgonvt\LaraPress\Blog;
use vicgonvt\LaraPress\Drivers\DatabaseDriver;
use vicgonvt\LaraPress\Drivers\FileDriver;

class DatabseDriverTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_throws_an_exception_if_the_db_table_is_not_found()
    {
        config(['larapress.database' => [
            'table' => 'fake_table_name',
        ]]);

        $this->expectException(\Exception::class);

        new FileDriver();
    }

    /** @test */
    public function file_driver_can_fetch_posts()
    {
        config(['larapress.database' => [
            'table' => 'blogs',
        ]]);

        foreach (File::files(__DIR__ . '/../stubs') as $file) {
            Blog::create([
                'data' => $file->getContents(),
            ]);
        }

        $driver = new DatabaseDriver();

        $this->assertCount(count(File::files(__DIR__ . '/../stubs')), $driver->fetchPosts());
    }
}
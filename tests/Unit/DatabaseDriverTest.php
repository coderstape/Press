<?php

namespace vicgonvt\LaraPress\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use vicgonvt\LaraPress\Blog;
use vicgonvt\LaraPress\Drivers\DatabaseDriver;
use vicgonvt\LaraPress\Drivers\FileDriver;
use vicgonvt\LaraPress\Exceptions\DatabaseTableNotFoundException;

class DatabaseDriverTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_throws_an_exception_if_the_db_table_is_not_found()
    {
        config(['larapress.database' => [
            'table' => 'fake_table_name',
        ], ['larapress.driver' => 'database']]);

        $this->expectException(DatabaseTableNotFoundException::class);

        new DatabaseDriver();
    }

    /** @test */
    public function file_driver_can_fetch_posts()
    {
        config(['larapress.database' => [
            'table' => 'blogs',
        ], ['larapress.driver' => 'database']]);

        foreach (File::files(__DIR__ . '/../stubs') as $file) {
            Blog::create([
                'data' => $file->getContents(),
            ]);
        }

        $driver = new DatabaseDriver();

        $this->assertCount(count(File::files(__DIR__ . '/../stubs')), $driver->fetchPosts());
    }
}
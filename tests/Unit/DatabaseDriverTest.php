<?php

namespace coderstape\Press\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use coderstape\Press\Blog;
use coderstape\Press\Drivers\DatabaseDriver;
use coderstape\Press\Drivers\FileDriver;
use coderstape\Press\Exceptions\DatabaseTableNotFoundException;

class DatabaseDriverTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_throws_an_exception_if_the_db_table_is_not_found()
    {
        config(['press.database' => [
            'table' => 'fake_table_name',
        ], 'press.driver' => 'database']);

        $this->expectException(DatabaseTableNotFoundException::class);

        new DatabaseDriver();
    }

    /** @test */
    public function database_driver_can_fetch_posts()
    {
        config([
            'press.database' => [
                'table' => 'blogs',
            ],
            'press.driver' => 'database',
        ]);

        foreach (File::files(__DIR__ . '/../stubs') as $file) {
            Blog::create([
                'data' => $file->getContents(),
            ]);
        }

        $driver = new DatabaseDriver();

        $this->assertCount(count(File::files(__DIR__ . '/../stubs')), $driver->fetchPosts());
    }
}
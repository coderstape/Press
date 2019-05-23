<?php

namespace coderstape\Press\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use coderstape\Press\Drivers\GistDriver;

/**
 * @group integration
 */
class GistDriverTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_fetch_the_gist_source()
    {
        config(['press.gist' => [
            'source' => '056ebe80f90f268bcae560d70f2c2508',
        ]]);

        $driver = new GistDriver();

        $this->assertCount(1, $driver->fetchPosts());
    }
}
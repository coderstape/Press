<?php

namespace coderstape\Press\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use coderstape\Press\Drivers\GistDriver;

// Attribute, not a doc-comment: PHPUnit 12 no longer reads
// annotation metadata, and this group MUST stay excluded by
// phpunit.xml (it calls the live GitHub Gist API).
#[Group('integration')]
class GistDriverTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_can_fetch_the_gist_source()
    {
        config(['press.gist' => [
            'source' => '056ebe80f90f268bcae560d70f2c2508',
        ]]);

        $driver = new GistDriver();

        $this->assertCount(1, $driver->fetchPosts());
    }
}
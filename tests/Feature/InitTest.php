<?php

namespace vicgonvt\LaraPress\Tests;

use Illuminate\Support\Facades\File;

class InitTest extends TestCase
{
    /** @test */
    public function it_can_find_the_test_markdown_files()
    {
        $this->assertTrue(File::exists(__DIR__ . '/../stubs/MarkFile1.md'));
    }
}

<?php

namespace coderstape\Press\Tests;

use Illuminate\Support\Facades\File;

class InitTest extends TestCase
{
    public function test_it_can_find_the_test_markdown_files()
    {
        $this->assertTrue(File::exists(__DIR__ . '/../stubs/MarkFile1.md'));
    }
}

<?php

namespace coderstape\Press\Tests;

use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;

class InitTest extends TestCase
{
    #[Test]
    public function it_can_find_the_test_markdown_files()
    {
        $this->assertTrue(File::exists(__DIR__ . '/../stubs/MarkFile1.md'));
    }
}

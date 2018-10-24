<?php

namespace vicgonvt\LaraPress\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use vicgonvt\LaraPress\PressFileParser;

class InitTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_find_the_test_markdown_files()
    {
        $this->assertTrue(File::exists(__DIR__ . '/../stubs/MarkFile1.md'));
    }

    /** @test */
//    public function experiment()
//    {
//        $data = (new PressFileParser(__DIR__ . '/../stubs/MarkFile1.md'));
//
//        die(var_dump($data->head()));
//    }
}
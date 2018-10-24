<?php

namespace vicgonvt\LaraPress\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use vicgonvt\LaraPress\PressFileParser;

class PressFileParserTest extends TestCase
{
    private $parser;

    protected function setUp()
    {
        parent::setUp();

        $this->parser = (new PressFileParser(__DIR__ . '/../stubs/MarkFile1.md'));
    }

    /** @test */
    public function it_can_parse_the_head()
    {
        $head = $this->parser->head();

        $this->assertEquals('Title in Title Bar', $head['title']);
        $this->assertEquals('keyword1, keyword2, keyword3', $head['keywords']);
        $this->assertEquals('Description here', $head['description']);
        $this->assertEquals('May 14 1988', $head['date']);
        $this->assertEquals('Tag 1, Tag 2', $head['tags']);
        $this->assertEquals('/blog/title-in-title-bar', $head['permalink']);
        $this->assertEquals('https://via.placeholder.com/500x140', $head['img']);
    }
}
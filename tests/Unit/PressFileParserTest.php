<?php

namespace vicgonvt\LaraPress\Tests;

use Carbon\Carbon;
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
        $data = $this->parser->getData();

        $this->assertEquals('Title in Title Bar', $data['title']);
        $this->assertEquals('keyword1, keyword2, keyword3', $data['keywords']);
        $this->assertEquals('Description here', $data['description']);
        $this->assertEquals('May 14 1988', $data['date']);
        $this->assertEquals('Tag 1, Tag 2', $data['tags']);
        $this->assertEquals('/blog/title-in-title-bar', $data['permalink']);
        $this->assertEquals('https://via.placeholder.com/500x140', $data['img']);
    }

    /** @test */
    public function it_parse_the_date_into_a_carbon_instance()
    {
        $data = $this->parser->getData();

        $this->assertInstanceOf(Carbon::class, $data['published_at']);
        $this->assertEquals('1988-05-14 00:00:00', $data['published_at']->toDatetimeString());
    }

    /** @test */
    public function it_sets_a_default_published_at_of_now_if_it_cant_parse()
    {
        $data = (new PressFileParser("---\nDate: Gibberish---\nSomething"))->getData();

        $this->assertInstanceOf(Carbon::class, $data['published_at']);
        $this->assertEquals(Carbon::now()->startOfDay(), $data['published_at']->toDatetimeString());
    }
}
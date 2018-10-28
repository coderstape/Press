<?php

namespace vicgonvt\LaraPress\Tests;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use vicgonvt\LaraPress\Facades\LaraPress;
use vicgonvt\LaraPress\Field\FieldContract;
use vicgonvt\LaraPress\PressFileParser;
use vicgonvt\LaraPress\Series;

class PressFileParserTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_parse_the_head()
    {
        $data = $this->getSampleMarkdownParser()->getData();

        $this->assertEquals('Title in Title Bar', $data['title']);
        $this->assertEquals('keyword1, keyword2, keyword3', $data['keywords']);
        $this->assertEquals('Description here', $data['description']);
        $this->assertEquals('May 14 1988', $data['date']);
        $this->assertEquals('Tag 1, Tag 2', $data['tags']);
        $this->assertEquals('title-in-title-bar', $data['permalink']);
        $this->assertEquals('https://via.placeholder.com/500x140', $data['img']);
    }

    /** @test */
    public function it_parse_the_date_into_a_carbon_instance()
    {
        $data = $this->getSampleMarkdownParser()->getData();

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

    /** @test */
    public function it_parses_permalink_into_slug_with_fallback()
    {
        $data = (new PressFileParser("---\nPermalink: some-random-string-here---\nSomething"))->getData();
        $this->assertEquals('some-random-string-here', $data['slug']);

        $data = (new PressFileParser("---\nPermalink: Another random sTrINg---\nSomething"))->getData();
        $this->assertEquals('another-random-string', $data['slug']);
    }

    /** @test */
    public function a_title_is_used_as_default_slug()
    {
        $data = (new PressFileParser("---\nTitle: A Cool Title---\nSomething"))->getData();
        $this->assertEquals('a-cool-title', $data['slug']);

        $data = (new PressFileParser("---\nTitle: A Cool Title\nPermalink: something-else---\nSomething"))->getData();
        $this->assertEquals('something-else', $data['slug']);

        $data = (new PressFileParser("---\nPermalink: something-else\nTitle: A Cool Title---\nSomething"))->getData();
        $this->assertEquals('something-else', $data['slug']);
    }
    
    /** @test */
    public function the_series_get_added_to_the_db()
    {
        $data = (new PressFileParser("---\nSeries: Adventure---\nSomething"))->getData();

        $series = Series::all();

        $this->assertCount(1, $series);
        $this->assertEquals('Adventure', $series->first()->title);
    }

    /** @test */
    public function it_doesnt_add_duplicate_series()
    {
        $data = (new PressFileParser("---\nSeries: Adventure---\nSomething"))->getData();

        $this->assertCount(1, Series::all());

        $data = (new PressFileParser("---\nSeries: Adventure---\nSomething"))->getData();

        $this->assertCount(1, Series::all()->fresh());
    }
    
    /** @test */
    public function series_edge_cases_with_different_case()
    {
        (new PressFileParser("---\nSeries: Adventure---\nSomething"))->getData();
        $this->assertCount(1, Series::all());

        (new PressFileParser("---\nSeries: adventure---\nSomething"))->getData();
        $this->assertCount(1, Series::all()->fresh());

        (new PressFileParser("---\nSeries: aDvEnTuRE---\nSomething"))->getData();
        $this->assertCount(1, Series::all()->fresh());
    }

    /** @test */
    public function single_extra_field_is_parsed_into_a_json()
    {
        $data = (new PressFileParser("---\nCustom Field: Some data---\nSomething"))->getData();

        $this->assertEquals(json_encode(['Custom Field' => 'Some data']), $data['extra']);
    }

    /** @test */
    public function two_or_more_extra_fields_are_parsed_into_a_json()
    {
        $data = (new PressFileParser("---\nCustom Field: Some data\nKeywords: one, two, three\nimages: img/path/file.jpg---\nSomething"))->getData();

        $expectedArray = [
            'Custom Field' => 'Some data',
            'Keywords' => 'one, two, three',
            'images' => 'img/path/file.jpg',
        ];
        $this->assertEquals(json_encode($expectedArray), $data['extra']);
    }

    /** @test */
    public function an_explicit_identifier_gets_added()
    {
        $data = (new PressFileParser("---\nIdentifier: 123456---\nSomething"))->getData();

        $this->assertEquals('123456', $data['identifier']);
    }

    /** @test */
    public function the_body_gets_markdown_parsed()
    {
        $data = (new PressFileParser("---\nTitle: A Cool Title---\n#Title Here"))->getData();
        $this->assertEquals('<h1>Title Here</h1>', $data['body']);
    }

    /** @test */
    public function it_can_use_a_users_class()
    {
        LaraPress::fields(['\vicgonvt\LaraPress\Tests\Other']);

        $data = (new PressFileParser("---\nOther: A Cool Title---\n#Title Here"))->getData();
        $this->assertEquals('A Cool Title', $data['other']);
    }

    /** @test */
    public function it_fulfills_the_full_class_name()
    {
        LaraPress::fields(['\vicgonvt\LaraPress\Tests\TitleTitle']);

        $data = (new PressFileParser("---\nTitle: A Cool Title---\n#Title Here"))->getData();
        $this->assertEquals('A Cool Title', $data['title']);
    }

    private function getSampleMarkdownParser()
    {
        return (new PressFileParser(__DIR__ . '/../stubs/MarkFile1.md'));
    }
}

class Other extends FieldContract
{
    public static function process($fieldType, $fieldValue, $fields)
    {
        return ['other' => $fieldValue];
    }
}

class TitleTitle extends FieldContract
{
    public static function process($fieldType, $fieldValue, $fields)
    {
        return ['titletitle' => $fieldValue];
    }
}
<?php

namespace coderstape\Press\Tests;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use coderstape\Press\Facades\Press;
use coderstape\Press\Field\FieldContract;
use coderstape\Press\PressFileParser;
use coderstape\Press\Series;

class PressFileParserTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_can_parse_the_head()
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

    public function test_it_parse_the_date_into_a_carbon_instance()
    {
        $data = $this->getSampleMarkdownParser()->getData();

        $this->assertInstanceOf(Carbon::class, $data['published_at']);
        $this->assertEquals('1988-05-14 00:00:00', $data['published_at']->toDatetimeString());
    }

    public function test_it_sets_a_default_published_at_of_now_if_it_cant_parse()
    {
        $data = (new PressFileParser("---\nDate: Gibberish---\nSomething"))->getData();

        $this->assertInstanceOf(Carbon::class, $data['published_at']);
        $this->assertEquals(Carbon::now()->startOfDay(), $data['published_at']->toDatetimeString());
    }

    public function test_it_parses_permalink_into_slug_with_fallback()
    {
        $data = (new PressFileParser("---\nPermalink: some-random-string-here---\nSomething"))->getData();
        $this->assertEquals('some-random-string-here', $data['slug']);

        $data = (new PressFileParser("---\nPermalink: Another random sTrINg---\nSomething"))->getData();
        $this->assertEquals('another-random-string', $data['slug']);
    }

    public function test_a_title_is_used_as_default_slug()
    {
        $data = (new PressFileParser("---\nTitle: A Cool Title---\nSomething"))->getData();
        $this->assertEquals('a-cool-title', $data['slug']);

        $data = (new PressFileParser("---\nTitle: A Cool Title\nPermalink: something-else---\nSomething"))->getData();
        $this->assertEquals('something-else', $data['slug']);

        $data = (new PressFileParser("---\nPermalink: something-else\nTitle: A Cool Title---\nSomething"))->getData();
        $this->assertEquals('something-else', $data['slug']);
    }
    
    public function test_the_series_get_added_to_the_db()
    {
        $data = (new PressFileParser("---\nSeries: Adventure---\nSomething"))->getData();

        $series = Series::all();

        $this->assertCount(1, $series);
        $this->assertEquals('Adventure', $series->first()->title);
    }

    public function test_it_doesnt_add_duplicate_series()
    {
        $data = (new PressFileParser("---\nSeries: Adventure---\nSomething"))->getData();

        $this->assertCount(1, Series::all());

        $data = (new PressFileParser("---\nSeries: Adventure---\nSomething"))->getData();

        $this->assertCount(1, Series::all()->fresh());
    }
    
    public function test_series_edge_cases_with_different_case()
    {
        (new PressFileParser("---\nSeries: Adventure---\nSomething"))->getData();
        $this->assertCount(1, Series::all());

        (new PressFileParser("---\nSeries: adventure---\nSomething"))->getData();
        $this->assertCount(1, Series::all()->fresh());

        (new PressFileParser("---\nSeries: aDvEnTuRE---\nSomething"))->getData();
        $this->assertCount(1, Series::all()->fresh());
    }

    public function test_single_extra_field_is_parsed_into_a_json()
    {
        $data = (new PressFileParser("---\nCustom Field: Some data---\nSomething"))->getData();

        $this->assertEquals(json_encode(['Custom Field' => 'Some data']), $data['extra']);
    }

    public function test_two_or_more_extra_fields_are_parsed_into_a_json()
    {
        $data = (new PressFileParser("---\nCustom Field: Some data\nKeywords: one, two, three\nimages: img/path/file.jpg---\nSomething"))->getData();

        $expectedArray = [
            'Custom Field' => 'Some data',
            'Keywords' => 'one, two, three',
            'images' => 'img/path/file.jpg',
        ];
        $this->assertEquals(json_encode($expectedArray), $data['extra']);
    }

    public function test_an_explicit_identifier_gets_added()
    {
        $data = (new PressFileParser("---\nIdentifier: 123456---\nSomething"))->getData();

        $this->assertEquals('123456', $data['identifier']);
    }

    public function test_the_body_gets_markdown_parsed()
    {
        $data = (new PressFileParser("---\nTitle: A Cool Title---\n#Title Here"))->getData();
        $this->assertEquals('<h1>Title Here</h1>', $data['body']);
    }

    public function test_it_can_use_a_users_class()
    {
        press::fields(['\coderstape\Press\Tests\Other']);

        $data = (new PressFileParser("---\nOther: A Cool Title---\n#Title Here"))->getData();
        $this->assertEquals('A Cool Title', $data['other']);
    }

    public function test_it_fulfills_the_full_class_name()
    {
        press::fields(['\coderstape\Press\Tests\TitleTitle']);

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
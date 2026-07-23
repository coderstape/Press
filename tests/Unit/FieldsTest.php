<?php

namespace coderstape\Press\Tests;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use coderstape\Press\Author as AuthorModel;
use coderstape\Press\Field\Author;
use coderstape\Press\Field\Date;
use coderstape\Press\Field\FieldContract;
use coderstape\Press\Field\Tags;
use coderstape\Press\PressFileParser;
use coderstape\Press\Tag;

class FieldsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_author_field_creates_the_author_and_returns_its_id()
    {
        $result = Author::process('Author', '  John Doe  ', []);

        $this->assertCount(1, AuthorModel::all());
        $this->assertEquals('John Doe', AuthorModel::first()->name);
        $this->assertEquals(['author_id' => AuthorModel::first()->id], $result);
    }

    #[Test]
    public function the_author_field_reuses_an_existing_author()
    {
        Author::process('Author', 'John Doe', []);
        Author::process('Author', 'John Doe', []);

        $this->assertCount(1, AuthorModel::all());
    }

    #[Test]
    public function an_author_head_flows_through_the_parser_into_author_id()
    {
        $data = (new PressFileParser("---\nAuthor: John Doe---\nBody"))->getData();

        $this->assertEquals(AuthorModel::first()->id, $data['author_id']);
    }

    #[Test]
    public function a_past_date_marks_the_post_active_and_a_future_date_inactive()
    {
        // Scheduled publishing lives here: ingest marks future-dated
        // posts inactive; re-running press:process after the date
        // passes flips them active.
        $past = Date::process('date', 'May 14 1988', []);
        $future = Date::process('date', Carbon::now()->addYear()->format('M d Y'), []);

        $this->assertTrue($past['active']);
        $this->assertFalse($future['active']);
    }

    #[Test]
    public function tags_are_trimmed_and_created_once_per_slug()
    {
        $result = Tags::process('tags', ' Tag 1 , Tag 2 ', []);

        $this->assertCount(2, Tag::all());
        $this->assertEquals(['Tag 1', 'Tag 2'], Tag::pluck('name')->all());
        $this->assertCount(2, $result['tag_ids']);
    }

    #[Test]
    public function a_case_variant_tag_reuses_the_existing_tag_instead_of_crashing()
    {
        // Regression pin: tags.slug is unique, and firstOrCreate used
        // to match on slug AND name, so 'laravel' after 'Laravel'
        // attempted a duplicate-slug insert. First spelling keeps the
        // display name.
        Tags::process('tags', 'Laravel', []);
        Tags::process('tags', 'laravel', []);

        $this->assertCount(1, Tag::all());
        $this->assertEquals('Laravel', Tag::first()->name);
    }

    #[Test]
    public function the_field_contract_default_passes_the_field_through_unchanged()
    {
        $this->assertEquals(
            ['anything' => 'value'],
            FieldContract::process('anything', 'value', [])
        );
    }
}

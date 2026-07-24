<?php

namespace coderstape\Press\Tests;

use Illuminate\Auth\GenericUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use coderstape\Press\Actions\Database;
use coderstape\Press\Author;
use coderstape\Press\Drivers\DatabaseDriver;
use coderstape\Press\Drivers\FileDriver;
use coderstape\Press\Drivers\GistDriver;
use coderstape\Press\Exceptions\UnsupportedDriverException;
use coderstape\Press\Press;
use coderstape\Press\Post;
use coderstape\Press\Series;
use coderstape\Press\Tag;
use coderstape\Press\Trending;

class PressTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_can_store_meta_information()
    {
        config(['press.blog' => [
            'field1' => 'test1',
            'field2' => 'test2',
        ]]);

        $press = new Press();

        $this->assertEquals('test1', $press->meta('field1'));
        $this->assertEquals('test2', $press->meta('field2'));
    }

    #[Test]
    public function it_can_set_a_parameter_with_an_array()
    {
        config(['press.blog' => [
            'field1' => 'test1',
            'field2' => 'test2',
        ]]);

        $press = new Press();
        $press->meta(['field3' => 'test3']);

        $this->assertEquals('test3', $press->meta('field3'));
    }

    #[Test]
    public function it_can_overwrite_an_existing_field()
    {
        config(['press.blog' => [
            'field1' => 'test1',
            'field2' => 'test2',
        ]]);

        $press = new Press();
        $press->meta(['field1' => 'new value']);

        $this->assertEquals('new value', $press->meta('field1'));
    }

    #[Test]
    public function it_can_parse_a_post_model_and_override_meta_tags()
    {
        $post = Post::factory()->create();

        $press = new Press();
        $press->meta($post);

        $this->assertEquals($post->title, $press->meta('title'));
        $this->assertEquals($post->extra('description'), $press->meta('description'));
        $this->assertEquals($post->extra('keywords'), $press->meta('keywords'));
        $this->assertEquals($post->extra('img'), $press->meta('image'));
        $this->assertEquals($post->path(), $press->meta('url'));
    }

    #[Test]
    public function it_can_parse_a_tag_and_override_meta_tags()
    {
        $tag = Tag::factory()->create();

        $press = new Press();
        $press->meta($tag);

        $this->assertEquals($tag->name, $press->meta('title'));
        $this->assertEquals(
            'Now showing only posts that are associated and filtered by the tag ' . $tag->name,
            $press->meta('description')
        );
        $this->assertEquals(str_replace(' ', ', ', $tag->name), $press->meta('keywords'));
        $this->assertEquals($tag->path(), $press->meta('url'));
    }

    #[Test]
    public function it_can_parse_a_series_and_override_meta_tags()
    {
        $series = Series::factory()->create();

        $press = new Press();
        $press->meta($series);

        $this->assertEquals($series->title, $press->meta('title'));
        $this->assertEquals(
            'Showing all posts in the series titled ' . $series->title,
            $press->meta('description')
        );
        $this->assertEquals(str_replace(' ', ', ', $series->title), $press->meta('keywords'));
        $this->assertEquals($series->path(), $press->meta('url'));
    }

    #[Test]
    public function driver_resolves_the_class_matching_the_configured_driver()
    {
        config(['press.driver' => 'file', 'press.file' => ['path' => __DIR__ . '/../stubs']]);
        $this->assertInstanceOf(FileDriver::class, Press::driver());

        config(['press.driver' => 'database', 'press.database' => ['table' => 'blogs']]);
        $this->assertInstanceOf(DatabaseDriver::class, Press::driver());

        config(['press.driver' => 'gist', 'press.gist' => ['source' => '']]);
        $this->assertInstanceOf(GistDriver::class, Press::driver());

        $this->assertInstanceOf(Database::class, Press::database());
    }

    #[Test]
    public function config_not_published_reports_a_missing_press_config()
    {
        // The provider deliberately does NOT mergeConfigFrom: a site
        // without the published config has config('press') === null,
        // which is what press:process warns about.
        $this->assertFalse(Press::configNotPublished());

        config(['press' => null]);

        $this->assertTrue(Press::configNotPublished());
    }

    #[Test]
    public function path_and_pagination_fall_back_to_defaults_when_unconfigured()
    {
        // The TestCase env sets neither key. Note the pagination
        // default is the STRING '15' -- pinned as-is; paginate()
        // accepts it, but don't tighten to int without checking
        // callers that might strict-compare.
        $press = new Press();

        $this->assertSame('/blog', $press->path());
        $this->assertSame('15', $press->pagination());
    }

    #[Test]
    public function meta_returns_the_whole_array_with_no_arguments_and_empty_string_for_unknown_keys()
    {
        config(['press.blog' => ['field1' => 'test1'], 'press.path' => '/blog']);

        $press = new Press();

        $this->assertEquals('test1', $press->meta()['field1']);
        $this->assertEquals('http://localhost/blog', $press->meta()['url']);
        $this->assertSame('', $press->meta('nope'));
    }

    #[Test]
    public function the_meta_url_falls_back_to_the_blog_path_default_when_press_path_is_unset()
    {
        // The constructor builds meta['url'] through path(), so an
        // unpublished press.path yields the same /blog default the
        // routes use. (Historically it read the config directly with
        // no default and produced the bare app URL here.)
        config(['press.blog' => []]);

        $press = new Press();

        $this->assertEquals('http://localhost/blog', $press->meta()['url']);
    }

    #[Test]
    public function meta_with_an_object_lacking_a_transformer_is_a_silent_no_op()
    {
        // Author (and Blog) have no Transformers class, so
        // AuthorController's Press::meta($author) quietly leaves the
        // default meta in place and returns null. If author pages ever
        // need real meta, the fix is a Transformers\Author class, not
        // loosening the guard.
        config(['press.blog' => ['title' => 'Default Title']]);

        $press = new Press();
        $author = Author::factory()->create(['name' => 'John Doe']);

        $this->assertNull($press->meta($author));
        $this->assertEquals('Default Title', $press->meta('title'));
    }

    #[Test]
    public function fields_merge_into_the_available_fields_list()
    {
        $press = new Press();
        $press->fields(['FieldA']);
        $press->fields(['FieldB']);

        $this->assertEquals(['FieldA', 'FieldB'], $press->availableFields());
    }

    #[Test]
    public function is_editor_is_false_for_guests_and_unlisted_users_and_true_for_listed_editors()
    {
        config(['press.blog' => []]);

        $press = new Press();
        $press->editors(['editor@example.com']);

        $this->assertFalse($press->isEditor());

        $this->actingAs(new GenericUser(['id' => 1, 'email' => 'someone@example.com']));
        $this->assertFalse($press->isEditor());

        $this->actingAs(new GenericUser(['id' => 2, 'email' => 'editor@example.com']));
        $this->assertTrue($press->isEditor());
    }

    #[Test]
    public function an_unsupported_driver_name_throws_a_named_exception()
    {
        // Was a raw PHP Error naming a class the config never
        // mentioned. The message pins the class it looked for, which
        // is also what makes the Str::title() casing legible to
        // whoever typo'd the config.
        config(['press.driver' => 'mongo']);

        $this->expectException(UnsupportedDriverException::class);
        $this->expectExceptionMessage('coderstape\Press\Drivers\MongoDriver');

        Press::driver();
    }

    #[Test]
    public function trending_limits_by_config_and_falls_back_to_the_documented_default()
    {
        Trending::factory()->count(3)->create();

        // Default asserted FIRST and through the SQL, deliberately:
        // the test env never sets trending_limit, and once config()
        // sets a key there is no unsetting it (null is a VALUE --
        // config()'s default only fires on a MISSING key). Proving
        // 1000 behaviorally would mean 1001 rows.
        DB::enableQueryLog();
        DB::flushQueryLog();
        Press::trending();
        $sql = DB::getQueryLog()[0]['query'];
        DB::disableQueryLog();

        $this->assertStringContainsString('limit 1000', $sql);

        config(['press.trending_limit' => 2]);

        $this->assertCount(2, Press::trending());
    }
}

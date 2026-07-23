<?php

namespace coderstape\Press\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use coderstape\Press\Post;
use coderstape\Press\Tag;

class ModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function table_names_are_derived_from_the_class_name_with_the_configured_prefix()
    {
        config(['press.prefix' => 'press_']);

        $this->assertEquals('press_posts', (new Post)->getTable());
        $this->assertEquals('press_tags', (new Tag)->getTable());

        config(['press.prefix' => '']);

        $this->assertEquals('posts', (new Post)->getTable());
    }

    #[Test]
    public function the_prefix_is_captured_at_construction_time()
    {
        // The base Model reads press.prefix in its constructor, so a
        // config change after instantiation does not move an existing
        // instance's table. Pinned so nobody "fixes" a stale-instance
        // symptom by reordering config loading.
        config(['press.prefix' => 'press_']);
        $post = new Post;

        config(['press.prefix' => 'other_']);

        $this->assertEquals('press_posts', $post->getTable());
    }

    #[Test]
    public function an_explicit_table_property_wins_over_derivation_and_prefixing()
    {
        config(['press.prefix' => 'press_']);

        $model = new class extends \coderstape\Press\Model {
            protected $table = 'explicit_table';
        };

        $this->assertEquals('explicit_table', $model->getTable());
    }

    #[Test]
    public function migrations_honor_the_configured_prefix()
    {
        // The TestCase env sets press.prefix to '' before migrations
        // run, so the tables come up unprefixed -- pinning that the
        // Migration base class actually consults the config.
        $this->assertTrue(Schema::hasTable('posts'));
        $this->assertFalse(Schema::hasTable('press_posts'));
    }
}

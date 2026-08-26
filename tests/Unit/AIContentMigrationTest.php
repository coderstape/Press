<?php

namespace coderstape\Press\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;

class AIContentMigrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ★ Press ships loadMigrationsFrom(), so this migration runs against
     * every consumer's database on their next deploy -- and Sportsman has
     * held an `a_i_contents` table since long before AIContent moved into
     * this package. Re-running the file must no-op, not abort the deploy.
     * Drop the hasTable() guard from up() and this test fails with
     * "table a_i_contents already exists".
     */
    #[Test]
    public function the_a_i_contents_migration_no_ops_when_the_table_already_exists()
    {
        $this->assertTrue(Schema::hasTable('a_i_contents'));

        $migration = require __DIR__
            . '/../../database/migrations/2025_08_26_151047_create_a_i_contents_table.php';

        $migration->up();

        $this->assertTrue(Schema::hasTable('a_i_contents'));
    }

    #[Test]
    public function the_migration_creates_the_table_when_it_is_absent()
    {
        Schema::drop('a_i_contents');
        $this->assertFalse(Schema::hasTable('a_i_contents'));

        $migration = require __DIR__
            . '/../../database/migrations/2025_08_26_151047_create_a_i_contents_table.php';

        $migration->up();

        $this->assertTrue(Schema::hasTable('a_i_contents'));
        $this->assertTrue(Schema::hasColumn('a_i_contents', 'contentable_type'));
        $this->assertTrue(Schema::hasColumn('a_i_contents', 'data'));
    }
}

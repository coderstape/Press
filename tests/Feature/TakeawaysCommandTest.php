<?php

namespace coderstape\Press\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Symfony\Component\Console\Exception\CommandNotFoundException;
use coderstape\Press\AIContent;
use coderstape\Press\Ai\TakeawaysWriter;
use coderstape\Press\Post;

class TakeawaysCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        RecordingTakeaways::$seen = [];
        config(['press.ai.driver' => RecordingTakeaways::class, 'press.ai.batch' => 5]);
    }

    #[Test]
    public function command_is_available()
    {
        try {
            $this->artisan('press:takeaways');
            $this->assertTrue(true);
        } catch (CommandNotFoundException $e) {
            $this->fail('Unable to locate the command \'press:takeaways\'');
        }
    }

    /**
     * ★ The stored shape is the one the site's blog view reads with
     * json_decode($post->contentable->data): a JSON string holding the array,
     * inside the json-cast column -- 543 production rows on 2026-09-07 start
     * with a quote. Store a PHP array instead and the raw column starts with
     * `[`, the attribute is an array, and json_decode(array) throws in the
     * view. All three assertions are the contract; each fails on that change.
     */
    #[Test]
    public function it_writes_takeaways_for_active_posts_in_the_shape_the_blog_view_reads()
    {
        $post = Post::factory()->create(['title' => 'Charging two batteries']);

        $this->artisan('press:takeaways')
            ->expectsOutput('Written: 1, Failed: 0')
            ->assertExitCode(0);

        $this->assertSame([$post->id], RecordingTakeaways::$seen);

        $raw = DB::table('a_i_contents')->where('contentable_id', $post->id)->value('data');
        $this->assertStringStartsWith('"', $raw);

        $content = $post->fresh()->contentable;
        $this->assertInstanceOf(AIContent::class, $content);
        $this->assertIsString($content->data);
        $this->assertSame(['One.', 'Two.', 'Three.'], json_decode($content->data));
        $this->assertSame(['One.', 'Two.', 'Three.'], $content->takeaways());
    }

    #[Test]
    public function posts_that_have_takeaways_and_inactive_posts_are_left_alone()
    {
        $done = Post::factory()->create();
        $done->contentable()->create(['data' => '["already"]']);
        Post::factory()->create(['active' => 0]);
        $pending = Post::factory()->create();

        $this->artisan('press:takeaways')->assertExitCode(0);

        $this->assertSame([$pending->id], RecordingTakeaways::$seen);
        $this->assertSame('["already"]', $done->fresh()->contentable->data);
        $this->assertSame(2, AIContent::count());
    }

    #[Test]
    public function the_batch_bounds_a_run_and_the_limit_option_overrides_it()
    {
        Post::factory()->count(4)->create();

        config(['press.ai.batch' => 2]);
        $this->artisan('press:takeaways')->expectsOutput('Written: 2, Failed: 0')->assertExitCode(0);
        $this->assertCount(2, RecordingTakeaways::$seen);

        RecordingTakeaways::$seen = [];
        $this->artisan('press:takeaways', ['--limit' => 1])->expectsOutput('Written: 1, Failed: 0')->assertExitCode(0);
        $this->assertCount(1, RecordingTakeaways::$seen);

        // The next run picks up where the last left off: nothing is written twice.
        $this->assertSame(3, AIContent::count());
    }

    #[Test]
    public function nothing_to_do_is_a_clean_exit_even_with_no_driver_configured()
    {
        config(['press.ai.driver' => DisabledTakeaways::class]);

        $this->artisan('press:takeaways')
            ->expectsOutput('Every active post has its takeaways.')
            ->assertExitCode(0);
    }

    #[Test]
    public function pending_posts_with_no_driver_configured_is_an_error_that_writes_nothing()
    {
        Post::factory()->create();
        config(['press.ai.driver' => DisabledTakeaways::class]);

        $this->artisan('press:takeaways')
            ->expectsOutputToContain('1 post(s) need takeaways and the AI layer is not configured')
            ->assertExitCode(1);

        $this->assertSame(0, AIContent::count());
    }

    /**
     * ★ One post's failure costs one post, never the run: the OpenAI
     * predecessor threw out of its first post and left the rest untouched on
     * every run for 36 hours. The run still exits 1 so the scheduler reports it.
     */
    #[Test]
    public function one_failing_post_does_not_end_the_run_but_the_run_reports_it()
    {
        $first = Post::factory()->create(['title' => 'Fine']);
        $boom = Post::factory()->create(['title' => 'boom']);
        $last = Post::factory()->create(['title' => 'Also fine']);
        config(['press.ai.driver' => FailingTakeaways::class]);

        $this->artisan('press:takeaways')
            ->expectsOutputToContain("Failed post {$boom->id} 'boom': RuntimeException: rate limited")
            ->expectsOutput('Written: 2, Failed: 1')
            ->assertExitCode(1);

        $this->assertNotNull($first->fresh()->contentable);
        $this->assertNull($boom->fresh()->contentable);
        $this->assertNotNull($last->fresh()->contentable);
    }

    #[Test]
    public function an_empty_answer_is_a_failure_not_an_empty_list_on_the_page()
    {
        $post = Post::factory()->create();
        config(['press.ai.driver' => EmptyTakeaways::class]);

        $this->artisan('press:takeaways')
            ->expectsOutputToContain('nothing returned')
            ->expectsOutput('Written: 0, Failed: 1')
            ->assertExitCode(1);

        $this->assertNull($post->fresh()->contentable);
    }
}

class RecordingTakeaways implements TakeawaysWriter
{
    /** @var list<int> */
    public static array $seen = [];

    public function enabled(): bool
    {
        return true;
    }

    public function write(Post $post): array
    {
        static::$seen[] = $post->id;

        return ['One.', 'Two.', 'Three.'];
    }
}

class FailingTakeaways extends RecordingTakeaways
{
    public function write(Post $post): array
    {
        if ($post->title === 'boom') {
            throw new RuntimeException('rate limited');
        }

        return parent::write($post);
    }
}

class EmptyTakeaways extends RecordingTakeaways
{
    public function write(Post $post): array
    {
        return [];
    }
}

class DisabledTakeaways extends RecordingTakeaways
{
    public function enabled(): bool
    {
        return false;
    }
}

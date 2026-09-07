<?php

namespace coderstape\Press\Console;

use Illuminate\Console\Command;
use Throwable;
use coderstape\Press\Ai\AnthropicTakeaways;
use coderstape\Press\Ai\TakeawaysWriter;
use coderstape\Press\Post;

/**
 * Writes the AI "Key Takeaways" tail for active posts that have none yet
 * (v1.1.0). The host schedules it; Sportsman ran its OpenAI predecessor every
 * six hours.
 *
 * Per-post isolation, Imagin's AltCommand lineage: one provider hiccup costs
 * one post, never the run, and the run still exits non-zero when anything
 * failed so the scheduler reports it -- the predecessor threw out of its
 * first post and left the other four untouched, every run, for 36 hours.
 *
 * ★ THE STORED SHAPE IS A JSON STRING INSIDE THE JSON COLUMN. AIContent casts
 * `data` to json and the predecessor stored the model's raw JSON text, so all
 * 543 rows on Sportsman (read 2026-09-07) hold `"[\"...\"]"` and the site's
 * blog view reads them with json_decode($post->contentable->data). Writing a
 * PHP array here would store a bare array, json_decode(array) would throw in
 * the view, and every new post would 500. Same shape, on purpose; the
 * AIContent::takeaways() helper reads both shapes for whoever migrates the
 * view later.
 */
class TakeawaysCommand extends Command
{
    /** Judgment call, veto ok: the predecessor's five posts per run. */
    public const DEFAULT_BATCH = 5;

    protected $signature = 'press:takeaways
                            {--limit= : Posts to write this run (default press.ai.batch)}';

    protected $description = 'Writes AI key takeaways for active posts that have none.';

    public function handle()
    {
        $limit = (int) ($this->option('limit') ?: config('press.ai.batch') ?: self::DEFAULT_BATCH);

        $posts = Post::query()
            ->where('active', 1)
            ->doesntHave('contentable')
            ->orderBy('id')
            ->limit(max(1, $limit))
            ->get();

        if ($posts->isEmpty()) {
            $this->info('Every active post has its takeaways.');

            return 0;
        }

        $writer = $this->laravel->make((string) (config('press.ai.driver') ?: AnthropicTakeaways::class));

        if (! $writer instanceof TakeawaysWriter || ! $writer->enabled()) {
            $this->error(sprintf(
                '%d post(s) need takeaways and the AI layer is not configured -- composer require anthropic-ai/sdk and set PRESS_AI_KEY (see config/press.php, the ai block).',
                $posts->count()
            ));

            return 1;
        }

        $written = 0;
        $failed = 0;

        foreach ($posts as $post) {
            try {
                $takeaways = $writer->write($post);

                if ($takeaways === []) {
                    throw new \RuntimeException('nothing returned');
                }

                $post->contentable()->create([
                    'data' => json_encode($takeaways, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ]);

                $written++;
                $this->line(sprintf('%d %s: %d takeaway(s)', $post->id, $post->title, count($takeaways)));
            } catch (Throwable $e) {
                $failed++;
                $this->warn(sprintf("Failed post %d '%s': %s: %s", $post->id, $post->title, get_class($e), $e->getMessage()));
            }
        }

        $this->info("Written: {$written}, Failed: {$failed}");

        return $failed > 0 ? 1 : 0;
    }
}

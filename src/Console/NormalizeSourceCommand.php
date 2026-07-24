<?php

namespace coderstape\Press\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use coderstape\Press\Blog;
use coderstape\Press\Facades\Press;
use coderstape\Press\MarkdownParser;

/**
 * Normalizes authored markdown so it parses the same way under a
 * spec-compliant parser as it does under Parsedown's leniency.
 *
 * THE SAFETY PROPERTY: for every source it touches, the command renders
 * the body BEFORE and AFTER through the current parser and refuses to
 * write when the two differ. A rule is not trusted because it looked
 * safe on a sample -- it is proven on each post, and anything that
 * fails the proof is held back and reported rather than written.
 *
 * The 'emphasis' rule is the exception and is therefore opt-in: moving
 * a space out of `** text **` genuinely changes today's output (the
 * bold loses a leading space), so it requires --allow-visible-change.
 *
 * Dry run by default. --apply is the only thing that writes.
 */
class NormalizeSourceCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'press:normalize-source
                            {--rule=* : headings, html-blocks, emphasis (default: headings, html-blocks)}
                            {--apply : Write the changes. Without this the command only reports.}
                            {--allow-visible-change : Permit rules that alter current rendering (emphasis).}
                            {--log= : Write a before/after record of every affected source to this file}';

    /**
     * @var string
     */
    protected $description = 'Normalize authored markdown so it survives a parser swap. Dry run unless --apply.';

    /**
     * Rules that must be provable no-ops under the current parser.
     *
     * @var array
     */
    protected const SAFE_RULES = ['headings', 'html-blocks', 'heading-close'];

    /**
     * Rules that knowingly change current rendering.
     *
     * @var array
     */
    protected const VISIBLE_RULES = ['emphasis'];

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        if (Press::configNotPublished()) {
            return $this->warn('Please publish the config file by running' .
                ' \'php artisan vendor:publish --tag=press-config\'');
        }

        // Scoped to the database driver on purpose. The file driver
        // would mean rewriting files on disk and the gist driver is
        // remote and read-only; neither is worth guessing at until
        // someone actually needs it.
        if (config('press.driver') !== 'database') {
            return $this->error(
                'press:normalize-source only supports the \'database\' driver, ' .
                'and the configured driver is \'' . config('press.driver') . '\'. ' .
                'Rewriting source files or remote gists is deliberately out of scope.'
            );
        }

        $rules = $this->rules();

        if ($rules === null) {
            return 1;
        }

        $this->line('Rules: ' . implode(', ', $rules) . ($this->option('apply') ? '' : '   (DRY RUN)'));

        $changed = [];
        $heldBack = [];

        foreach (Blog::all() as $blog) {
            $before = (string) $blog->data;
            $after = $this->normalize($before, $rules);

            if ($after === $before) {
                continue;
            }

            $renderedBefore = $this->renderBody($before);
            $renderedAfter = $this->renderBody($after);

            $record = [
                'id' => $blog->id,
                'before' => $before,
                'after' => $after,
                'rendering_changed' => $renderedBefore !== $renderedAfter,
                'rendered_before' => $renderedBefore,
                'rendered_after' => $renderedAfter,
            ];

            // The proof. A safe-rule run that changes rendering means
            // the rule misfired on this post's content -- hold it back
            // and let a human look, never write it.
            if ($record['rendering_changed'] && ! $this->option('allow-visible-change')) {
                $heldBack[] = $record;

                continue;
            }

            $changed[] = $record;
        }

        return $this->finish($changed, $heldBack);
    }

    /**
     * @return array|null
     */
    protected function rules()
    {
        $rules = $this->option('rule') ?: static::SAFE_RULES;

        $known = array_merge(static::SAFE_RULES, static::VISIBLE_RULES);

        if ($unknown = array_diff($rules, $known)) {
            $this->error('Unknown rule(s): ' . implode(', ', $unknown) .
                '. Known rules: ' . implode(', ', $known) . '.');

            return null;
        }

        if (array_intersect($rules, static::VISIBLE_RULES) && ! $this->option('allow-visible-change')) {
            $this->error(
                'The \'emphasis\' rule changes how posts render under the CURRENT parser ' .
                '(bold loses a leading space), so it needs --allow-visible-change. ' .
                'Run it separately from the safe rules so the two kinds of change land in different deploys.'
            );

            return null;
        }

        return $rules;
    }

    /**
     * Apply the selected rules to one source document.
     *
     * @return string
     */
    protected function normalize($source, array $rules)
    {
        if (in_array('headings', $rules, true)) {
            // '##Heading' is an h2 under Parsedown and a paragraph with
            // visible hashes under CommonMark, which requires a space
            // after the # run.
            $source = preg_replace('/^(\s{0,3}#{1,6})([^#\s])/m', '$1 $2', $source);
        }

        if (in_array('html-blocks', $rules, true)) {
            // A VOID element alone on a line, immediately followed by
            // markdown, gets swallowed into the HTML block by CommonMark
            // until a blank line arrives. Restricted to void elements
            // deliberately: inserting a blank line after an OPENING
            // container tag (<div>) does change Parsedown's output.
            $source = preg_replace(
                '/^(\s{0,3}<(?:br|img|hr)\b[^>]*>)\R(?=\s{0,3}(?:#{1,6}|[*+-]\s|\d+\.\s|>))/mi',
                "$1\n\n",
                $source
            );
        }

        if (in_array('heading-close', $rules, true)) {
            // '## Heading##' -- CommonMark only reads a trailing # run
            // as a CLOSING sequence when whitespace precedes it, so
            // otherwise the hashes render as literal text at the end of
            // the heading. Parsedown strips them either way, which is
            // why removing them is a no-op today.
            $source = preg_replace('/^(\s{0,3}#{1,6}\s+.*?[^\s#])#+[ \t]*$/m', '$1', $source);
        }

        if (in_array('emphasis', $rules, true)) {
            // Whitespace immediately INSIDE a delimiter pair stops it
            // forming emphasis under CommonMark's flanking rules -- on
            // EITHER side. The first cut of this rule only handled a
            // space after the OPENING delimiter and silently skipped
            // '**text **', which turned out to be the more common
            // spelling in the real corpus: it left 6 posts unfixed
            // while reporting success. Trim both ends instead.
            // Double first, then single. Inner content may not contain
            // '*', and the single pass refuses a '*' adjacent to another,
            // so '**bold**' is immune to the second pass. An earlier cut
            // used one alternation for both and matched ACROSS delimiter
            // pairs -- '**a** and *b*' came out as '**a**and*b*'.
            $trim = function ($delimiter) {
                return function ($match) use ($delimiter) {
                    $inner = trim($match[1], " \t");

                    return $inner === '' ? $match[0] : $delimiter . $inner . $delimiter;
                };
            };

            $source = preg_replace_callback('/\*\*([^*\n]*?)\*\*/', $trim('**'), $source);
            $source = preg_replace_callback('/(?<!\*)\*(?!\*)([^*\n]*?)\*(?!\*)/', $trim('*'), $source);
        }

        return $source;
    }

    /**
     * Render just the body, the way ingest would.
     *
     * Mirrors PressFileParser's head/body split rather than calling it:
     * the full field pipeline has side effects (Field\Tags::process()
     * calls firstOrCreate and would write tag rows during what is
     * supposed to be a dry run).
     *
     * @return string
     */
    protected function renderBody($source)
    {
        preg_match('/^\-{3}(.*?)\-{3}(.*)/s', $source, $parts);

        return MarkdownParser::parse(trim($parts[2] ?? $source));
    }

    /**
     * @return int
     */
    protected function finish(array $changed, array $heldBack)
    {
        $this->newLine();
        $this->line(sprintf('Sources needing normalization: %d', count($changed) + count($heldBack)));
        $this->line(sprintf('  proven safe to write:       %d', count($changed)));
        $this->line(sprintf('  HELD BACK (rendering would change): %d', count($heldBack)));

        if ($heldBack) {
            $this->newLine();
            $this->warn('Held back — a safe rule changed this post\'s rendering, so it was NOT written:');

            foreach ($heldBack as $record) {
                $this->line('  blog id ' . $record['id']);
            }

            $this->line('Inspect these by hand; the --log file has their before/after.');
        }

        $this->writeLog(array_merge($changed, $heldBack));

        if ( ! $this->option('apply')) {
            $this->newLine();
            $this->comment('Dry run: nothing was written. Re-run with --apply to commit the proven-safe changes.');

            return 0;
        }

        foreach ($changed as $record) {
            Blog::where('id', $record['id'])->update(['data' => $record['after']]);
        }

        $this->newLine();
        $this->info(sprintf('Wrote %d source(s). Run \'php artisan press:process\' to regenerate the posts.', count($changed)));

        return 0;
    }

    /**
     * @return void
     */
    protected function writeLog(array $records)
    {
        if ( ! $path = $this->option('log')) {
            return;
        }

        $out = [];

        foreach ($records as $record) {
            $out[] = str_repeat('=', 72);
            $out[] = 'blog id ' . $record['id'] .
                ($record['rendering_changed'] ? '   *** RENDERING CHANGES ***' : '   (rendering identical)');
            $out[] = str_repeat('=', 72);
            $out[] = '--- SOURCE BEFORE ---';
            $out[] = $record['before'];
            $out[] = '--- SOURCE AFTER ---';
            $out[] = $record['after'];

            if ($record['rendering_changed']) {
                $out[] = '--- RENDERED BEFORE ---';
                $out[] = $record['rendered_before'];
                $out[] = '--- RENDERED AFTER ---';
                $out[] = $record['rendered_after'];
            }

            $out[] = '';
        }

        File::put($path, implode("\n", $out) . "\n");

        $this->info('Before/after log written to ' . $path);
    }
}

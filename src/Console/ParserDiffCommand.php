<?php

namespace coderstape\Press\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use League\CommonMark\CommonMarkConverter;
use coderstape\Press\Facades\Press;
use coderstape\Press\MarkdownParser;

/**
 * Renders every post's markdown source through BOTH Parsedown (what
 * produced the bodies sitting in the database today) and
 * league/commonmark, then reports how they differ.
 *
 * READ-ONLY BY CONSTRUCTION. It never writes to posts, blogs, or the
 * source files -- it fetches through the driver and throws the results
 * away. The point is to size a migration before committing to one
 * (roadmap 3), because stored bodies were rendered at INGEST: swapping
 * parsers doesn't rewrite history, it changes what the next
 * press:process produces for every post at once.
 *
 * Both passes go through MarkdownParser::$renderer, so nothing in the
 * ingest path is special-cased for this command.
 */
class ParserDiffCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'press:parser-diff
                            {--report= : Write full diffs for every differing post to this file}
                            {--show=3 : Print this many full diffs inline}';

    /**
     * @var string
     */
    protected $description = 'Diff Parsedown against league/commonmark across every post. Read-only.';

    /**
     * Bodies longer than this (in lines) skip the LCS diff, which is
     * O(n*m) in memory. They still get counted and categorized.
     */
    protected const MAX_DIFF_LINES = 2000;

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

        if ( ! class_exists(CommonMarkConverter::class)) {
            return $this->error(
                'league/commonmark is not installed. Run ' .
                '\'composer require league/commonmark\' -- note Laravel already ' .
                'requires it, so this is usually only missing in a stripped-down app.'
            );
        }

        try {
            $old = $this->parsedownPass();
            $sources = [];
            $new = $this->commonMarkPass($sources);
        } catch (\Exception $e) {
            MarkdownParser::$renderer = null;

            return $this->error($e->getMessage());
        }

        if (empty($old)) {
            return $this->warn('No posts found for the \'' . config('press.driver') . '\' driver.');
        }

        return $this->report($this->compare($old, $new, $sources));
    }

    /**
     * Baseline pass: the default path, exactly as press:process runs it.
     *
     * @return array
     */
    protected function parsedownPass()
    {
        MarkdownParser::$renderer = null;

        return Press::driver()->fetchPosts() ?: [];
    }

    /**
     * Candidate pass. The seam also records the raw source it was
     * handed, which is how source-level categorization gets its input
     * without any driver reaching back into the file/blob it came from.
     *
     * @param  array  $sources  filled by reference, in fetch order
     *
     * @return array
     */
    protected function commonMarkPass(&$sources)
    {
        // Stated explicitly rather than inherited: these ARE
        // league/commonmark's defaults, and they match the posture
        // Press has today (Parsedown with safe mode off). Making the
        // raw-HTML decision visible is a standing requirement -- see
        // the briefing's rejected-items list.
        $converter = new CommonMarkConverter([
            'html_input' => 'allow',
            'allow_unsafe_links' => true,
        ]);

        MarkdownParser::$renderer = function ($text) use (&$sources, $converter) {
            $sources[] = $text;

            return (string) $converter->convert($text);
        };

        try {
            return Press::driver()->fetchPosts() ?: [];
        } finally {
            // Static seam -- see the warning on MarkdownParser::$renderer.
            MarkdownParser::$renderer = null;
        }
    }

    /**
     * Pair the two passes up by identifier and classify each post.
     *
     * @return array
     */
    protected function compare(array $old, array $new, array $sources)
    {
        $newByIdentifier = [];

        foreach ($new as $post) {
            $newByIdentifier[$post['identifier']] = $post;
        }

        // Both passes walk the same driver in the same order, so the
        // Nth recorded source belongs to the Nth post. Verified rather
        // than assumed: a mismatch disables source-level categories
        // instead of silently mislabelling them.
        $aligned = count($sources) === count($old);

        if ( ! $aligned) {
            $this->warn('Source capture did not align with the post list; ' .
                'source-level categories are suppressed for this run.');
        }

        $results = [];

        foreach ($old as $index => $post) {
            $identifier = $post['identifier'];

            if ( ! isset($newByIdentifier[$identifier])) {
                continue;
            }

            $oldBody = (string) $post['body'];
            $newBody = (string) $newByIdentifier[$identifier]['body'];
            $source = $aligned ? (string) ($sources[$index] ?? '') : '';

            $results[] = [
                'identifier' => $identifier,
                'title' => $post['title'] ?? '(untitled)',
                'old' => $oldBody,
                'new' => $newBody,
                'categories' => $this->categorize($source, $oldBody, $newBody),
            ];
        }

        return $results;
    }

    /**
     * Classify one post's difference.
     *
     * Ordered from most trivial to most structural, and the trivial
     * tiers return alone: a post whose only delta is a trailing newline
     * should not also be reported under a source-level predictor, or
     * the summary reads far more alarming than the situation is.
     *
     * @return array
     */
    protected function categorize($source, $oldBody, $newBody)
    {
        if ($oldBody === $newBody) {
            return ['identical'];
        }

        if (trim($oldBody) === trim($newBody)) {
            return ['trailing-whitespace-only'];
        }

        if ($this->collapse($oldBody) === $this->collapse($newBody)) {
            return ['whitespace-only'];
        }

        $categories = [];

        // THE pin that must not move. The Imagin directive survives
        // ingest as literal text with '=>' entity-escaped; the
        // render-time expander's regex depends on both facts.
        if (str_contains($source, '@imagin(')) {
            $countChanged = substr_count($oldBody, '@imagin(') !== substr_count($newBody, '@imagin(');
            $escapeChanged = str_contains($oldBody, '=&gt;') !== str_contains($newBody, '=&gt;');

            if ($countChanged || $escapeChanged) {
                $categories[] = 'imagin-directive-changed';
            }
        }

        // CommonMark requires a space after the '#' run; Parsedown does
        // not. '#Heading' is an h1 today and a paragraph after a swap.
        // This repo's own admin test fixture uses that spelling.
        if (preg_match('/^#{1,6}[^#\s]/m', $source)) {
            $categories[] = 'atx-heading-needs-space';
        }

        if (preg_match('/^\s{0,3}[*+-][^\s*+-]/m', $source)) {
            $categories[] = 'list-marker-needs-space';
        }

        if (preg_match('/^\s{0,3}<[a-zA-Z!\/]/m', $source)) {
            $categories[] = 'raw-html-block';
        }

        if (preg_match('/^\s{0,3}>[^\s>]/m', $source)) {
            $categories[] = 'blockquote-needs-space';
        }

        if (empty($categories)) {
            $categories[] = 'uncategorized';
        }

        return $categories;
    }

    /**
     * Whitespace-insensitive form, for telling cosmetic deltas from
     * structural ones.
     *
     * @return string
     */
    protected function collapse($html)
    {
        return preg_replace('/\s+/', ' ', trim($html));
    }

    /**
     * @return int
     */
    protected function report(array $results)
    {
        $total = count($results);
        $identical = 0;
        $counts = [];
        $differing = [];

        foreach ($results as $result) {
            if ($result['categories'] === ['identical']) {
                $identical++;

                continue;
            }

            $differing[] = $result;

            foreach ($result['categories'] as $category) {
                $counts[$category] = ($counts[$category] ?? 0) + 1;
            }
        }

        $this->newLine();
        $this->info('Parser diff: Parsedown (current) vs league/commonmark');
        $this->line(str_repeat('-', 56));
        $this->line(sprintf('Posts examined:  %d', $total));
        $this->line(sprintf('Identical:       %d  (%s)', $identical, $this->percent($identical, $total)));
        $this->line(sprintf('Differing:       %d  (%s)', count($differing), $this->percent(count($differing), $total)));

        if ($counts) {
            arsort($counts);

            $this->newLine();
            $this->table(['Category', 'Posts'], array_map(
                fn ($category, $count) => [$category, $count],
                array_keys($counts),
                $counts
            ));
        }

        if (isset($counts['imagin-directive-changed'])) {
            $this->newLine();
            $this->error('STOP: the Imagin directive changed shape in ' .
                $counts['imagin-directive-changed'] . ' post(s). The render-time ' .
                'expander depends on it surviving ingest as literal text with ' .
                '\'=>\' entity-escaped. Re-derive those pins before going further.');
        }

        $this->showDiffs($differing);
        $this->writeReport($differing);

        $this->newLine();
        $this->comment('Read-only: nothing was written. Stored bodies still hold their Parsedown output.');

        return 0;
    }

    /**
     * @return void
     */
    protected function showDiffs(array $differing)
    {
        $show = (int) $this->option('show');

        foreach (array_slice($differing, 0, max(0, $show)) as $result) {
            $this->newLine();
            $this->line('=== ' . $result['title'] . ' [' . $result['identifier'] . '] ' .
                '(' . implode(', ', $result['categories']) . ')');

            foreach ($this->diff($result['old'], $result['new']) as $line) {
                if (str_starts_with($line, '-')) {
                    $this->line('<fg=red>' . $line . '</>');
                } elseif (str_starts_with($line, '+')) {
                    $this->line('<fg=green>' . $line . '</>');
                } else {
                    $this->line($line);
                }
            }
        }
    }

    /**
     * @return void
     */
    protected function writeReport(array $differing)
    {
        if ( ! $path = $this->option('report')) {
            return;
        }

        $out = ['Parsedown vs league/commonmark — ' . count($differing) . ' differing post(s)', ''];

        foreach ($differing as $result) {
            $out[] = str_repeat('=', 72);
            $out[] = $result['title'] . ' [' . $result['identifier'] . ']';
            $out[] = 'categories: ' . implode(', ', $result['categories']);
            $out[] = str_repeat('=', 72);
            $out = array_merge($out, $this->diff($result['old'], $result['new']), ['']);
        }

        File::put($path, implode("\n", $out) . "\n");

        $this->newLine();
        $this->info('Full diffs written to ' . $path);
    }

    /**
     * Line diff via longest-common-subsequence. Only changed lines and
     * their immediate neighbours would be ideal, but full context is
     * more useful in a report you are going to grep.
     *
     * @return array
     */
    protected function diff($old, $new)
    {
        $a = preg_split('/\R/', rtrim($old));
        $b = preg_split('/\R/', rtrim($new));
        $m = count($a);
        $n = count($b);

        if ($m > static::MAX_DIFF_LINES || $n > static::MAX_DIFF_LINES) {
            return ['  (body too large to diff: ' . $m . ' lines vs ' . $n . ')'];
        }

        $lcs = array_fill(0, $m + 1, array_fill(0, $n + 1, 0));

        for ($i = $m - 1; $i >= 0; $i--) {
            for ($j = $n - 1; $j >= 0; $j--) {
                $lcs[$i][$j] = $a[$i] === $b[$j]
                    ? $lcs[$i + 1][$j + 1] + 1
                    : max($lcs[$i + 1][$j], $lcs[$i][$j + 1]);
            }
        }

        $out = [];
        $i = $j = 0;

        while ($i < $m && $j < $n) {
            if ($a[$i] === $b[$j]) {
                $out[] = '  ' . $a[$i];
                $i++;
                $j++;
            } elseif ($lcs[$i + 1][$j] >= $lcs[$i][$j + 1]) {
                $out[] = '- ' . $a[$i];
                $i++;
            } else {
                $out[] = '+ ' . $b[$j];
                $j++;
            }
        }

        while ($i < $m) {
            $out[] = '- ' . $a[$i++];
        }

        while ($j < $n) {
            $out[] = '+ ' . $b[$j++];
        }

        return $out;
    }

    /**
     * @return string
     */
    protected function percent($part, $whole)
    {
        return $whole ? number_format($part / $whole * 100, 1) . '%' : '0.0%';
    }
}

<?php

namespace coderstape\Press;

use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\Autolink\AutolinkExtension;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\MarkdownConverter;
use Parsedown;

class MarkdownParser
{
    /**
     * Optional renderer override, consulted before the configured
     * parser. Mirrors ImaginShortcode::$renderer and exists for the
     * same reason: it is the seam that lets tooling swap the markdown
     * implementation without touching the ingest path.
     * press:parser-diff renders the same sources through two parsers in
     * one run through this seam. Signature: fn (string $text): string.
     *
     * ALWAYS restore this to null in a finally block. It is static, so
     * a leaked renderer silently changes every later ingest in the same
     * process.
     *
     * @var callable|null
     */
    public static $renderer = null;

    /**
     * Built once and reused. press:process renders the entire corpus in
     * a single run, and rebuilding the Environment per post is pure
     * waste; the converter is stateless, so sharing it is safe.
     *
     * @var \League\CommonMark\MarkdownConverter|null
     */
    protected static $converter = null;

    /**
     * Given a markdown string, it will pass back a parsed string.
     *
     * @param $text
     *
     * @return string
     */
    public static function parse($text)
    {
        // Trimmed on EVERY path, including the injected renderer.
        // CommonMark terminates its output with a newline and Parsedown
        // does not; left alone, that lone character would rewrite the
        // stored body of every post in the corpus on the first
        // press:process after a swap, and show up in press:parser-diff
        // as a 'trailing-whitespace-only' difference on a third of the
        // blog. It has never been meaningful in HTML output.
        return trim(static::render($text));
    }

    /**
     * Render through whichever parser is in play, untrimmed.
     *
     * @param $text
     *
     * @return string
     */
    protected static function render($text)
    {
        if (static::$renderer) {
            return call_user_func(static::$renderer, $text);
        }

        // Parsedown stays selectable rather than being deleted: it is
        // the baseline press:parser-diff compares against, and it is
        // what an un-migrated blog still runs. See the 'parser' block
        // in the config for the required migration ORDER.
        if (config('press.parser', 'commonmark') === 'parsedown') {
            return Parsedown::instance()->text($text);
        }

        return (string) static::converter()->convert($text);
    }

    /**
     * The CommonMark converter, configured to match what Press
     * actually needs.
     *
     * @return \League\CommonMark\MarkdownConverter
     */
    public static function converter()
    {
        if (static::$converter === null) {
            // html_input/allow_unsafe_links ARE league/commonmark's
            // defaults, but they are stated rather than inherited: raw
            // HTML passthrough is a deliberate Press decision (posts
            // rely on it for embeds) and the briefing requires any
            // parser swap to make that choice visible rather than
            // inherit it.
            $environment = new Environment([
                'html_input' => 'allow',
                'allow_unsafe_links' => true,
            ]);

            $environment->addExtension(new CommonMarkCoreExtension());

            // Not optional. Parsedown autolinks bare URLs and
            // CommonMark core does not, because autolinking is a GFM
            // extension -- without this, 16 posts in the real corpus
            // silently LOST links. It also autolinks bare 'www.' hosts
            // and email addresses, which Parsedown leaves as plain
            // text; that is a deliberate, accepted improvement.
            $environment->addExtension(new AutolinkExtension());

            static::$converter = new MarkdownConverter($environment);
        }

        return static::$converter;
    }
}

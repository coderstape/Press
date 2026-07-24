<?php

namespace coderstape\Press;

use Parsedown;

class MarkdownParser
{
    /**
     * Optional renderer override, consulted before Parsedown.
     *
     * Mirrors ImaginShortcode::$renderer and exists for the same
     * reason: it is the seam that lets tooling swap the markdown
     * implementation without touching the ingest path.
     * press:parser-diff uses it to render the same sources through two
     * parsers in one run, and it is the shape a league/commonmark
     * migration would take (roadmap 3). Signature:
     * fn (string $text): string.
     *
     * ALWAYS restore this to null in a finally block. It is static, so
     * a leaked renderer silently changes every later ingest in the same
     * process -- including a press:process that follows in the same
     * request or command run.
     *
     * @var callable|null
     */
    public static $renderer = null;

    /**
     * Given a markdown string, it will pass back a parsed string.
     *
     * @param $text
     *
     * @return string
     */
    public static function parse($text)
    {
        if (static::$renderer) {
            return call_user_func(static::$renderer, $text);
        }

        return Parsedown::instance()->text($text);
    }
}

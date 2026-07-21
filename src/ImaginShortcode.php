<?php

namespace coderstape\Press;

/**
 * Expands @imagin(...) shortcodes in stored post bodies at render time.
 *
 * Why this exists: post bodies are markdown, parsed by Parsedown at
 * ingest and stored as HTML. Parsedown treats an @imagin(...) line as
 * plain text -- it survives into the stored body as a literal (with =>
 * entity-escaped to =&gt;), wrapped in a <p>. It is NOT a Blade template
 * at that point, and running the stored body through Blade's compiler
 * only ever produced dead <?php echo ... ?> text in the page (it worked
 * historically only because Imagin's old directive eval()'d at compile
 * time -- a bug, since fixed). The correct pipeline is: markdown parses
 * at ingest, and Imagin renders at request time -- its markup is
 * auth-dependent (editors get data-imagin-* attributes) and cache-
 * invalidated, so it can never be baked into the stored body.
 *
 * This class is that request-time step. It finds @imagin(...) literals
 * in the stored HTML and replaces them with Imagin::image() output.
 * Nothing is ever eval()'d: only 'key' => 'value' single-quoted string
 * literal pairs are accepted, so runtime variables ($slug) are not
 * supported inside post bodies -- anything else is left untouched as
 * visible literal text rather than silently dropped or executed.
 *
 * Keys are whitelisted (mirroring Imagin's image-location endpoint
 * validation, and for the same reason): attribute VALUES are escaped
 * when Imagin spreads them into markup, but attribute NAMES are not,
 * and here the names come from author-controlled blog content calling
 * Imagin::image() directly, which has no whitelist of its own. The
 * whitelist is the attribute-name-injection defense.
 *
 * A <p> wrapping a directive that is the paragraph's sole content is
 * unwrapped: Parsedown produces <p>@imagin(...)</p>, and while an <img>
 * may legally sit in a paragraph, Imagin's empty-location placeholder
 * is a <div>, which may not -- browsers force-close the <p> and
 * shatter the surrounding layout.
 *
 * Press does not depend on Imagin: if the facade class is absent, and
 * no renderer is injected, bodies pass through unchanged.
 */
class ImaginShortcode
{
    /**
     * Params allowed through to Imagin::image(). Mirrors the
     * image-location endpoint whitelist -- carry that rationale before
     * loosening this.
     *
     * @var array
     */
    protected const ALLOWED_KEYS = [
        'location', 'width', 'height', 'alt', 'class', 'style',
        'loading', 'decoding', 'fetchpriority', 'sizes',
    ];

    /**
     * Matches a directive either as a paragraph's sole content (group 1,
     * the <p> is consumed and thereby unwrapped) or inline (group 2).
     * The expression may contain parentheses inside single-quoted
     * values ('alt' => 'Open (402)') but not elsewhere.
     *
     * @var string
     */
    protected const PATTERN =
        "/<p>\s*@imagin\(((?:[^()']|'[^']*')*)\)\s*<\/p>|@imagin\(((?:[^()']|'[^']*')*)\)/";

    /**
     * Optional renderer override, consulted before the Imagin facade.
     * A site can set this to customize rendering; Press's own tests use
     * it to exercise expansion without Imagin installed. Signature:
     * fn (array $params): string.
     *
     * @var callable|null
     */
    public static $renderer = null;

    /**
     * Expand every @imagin(...) occurrence in the given HTML.
     *
     * @param  string  $html
     * @param  callable|null  $renderer  fn (array $params): string --
     *         defaults to static::$renderer, then to Imagin::image()
     *         when the facade exists.
     *
     * @return string
     */
    public static function expand($html, ?callable $renderer = null)
    {
        $renderer = $renderer ?? static::$renderer ?? static::defaultRenderer();

        if ($renderer === null) {
            return $html;
        }

        return preg_replace_callback(static::PATTERN, function ($matches) use ($renderer) {
            $expression = $matches[1] !== '' ? $matches[1] : ($matches[2] ?? '');

            $params = static::parseExpression($expression);

            // Malformed, non-literal, or missing a location: leave the
            // original text in place -- visible and harmless beats
            // silently vanished or evaluated.
            if ($params === null || ! isset($params['location'])) {
                return $matches[0];
            }

            return $renderer($params);
        }, $html);
    }

    /**
     * Parse a directive expression into params, or null if it is
     * anything other than a comma-separated list of single-quoted
     * 'key' => 'value' string literal pairs.
     *
     * The stored body carries Parsedown's entity escaping (=&gt;), so
     * the expression is entity-decoded first; a decode of an already
     * unescaped => is a no-op, which keeps both stored forms working.
     *
     * @param  string  $expression
     *
     * @return array|null
     */
    protected static function parseExpression($expression)
    {
        $expression = html_entity_decode($expression, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        $pair = "'[^']*'\s*=>\s*'[^']*'";

        if (! preg_match("/^\s*{$pair}\s*(,\s*{$pair}\s*)*,?\s*$/", $expression)) {
            return null;
        }

        preg_match_all("/'([^']*)'\s*=>\s*'([^']*)'/", $expression, $matches, PREG_SET_ORDER);

        $params = [];

        foreach ($matches as $match) {
            if (in_array($match[1], static::ALLOWED_KEYS, true)) {
                $params[$match[1]] = $match[2];
            }
        }

        return $params;
    }

    /**
     * Imagin's facade, when the package is installed alongside Press.
     *
     * @return callable|null
     */
    protected static function defaultRenderer()
    {
        if (! class_exists(\GrandeBerg\Imagin\Facades\Imagin::class)) {
            return null;
        }

        return function (array $params) {
            return \GrandeBerg\Imagin\Facades\Imagin::image($params);
        };
    }
}

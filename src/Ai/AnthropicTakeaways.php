<?php

namespace coderstape\Press\Ai;

use RuntimeException;
use coderstape\Press\Post;

/**
 * The shipped TakeawaysWriter: Claude via the official anthropic-ai/sdk,
 * a SUGGESTED dependency -- enabled() reports false until the site
 * composer-requires it and sets press.ai.key.
 *
 * Ported from Sportsman's smn:ai-key-takeaways-on-blog (OpenAI, gpt-5-nano)
 * on 2026-09-07 after it failed every six-hourly run for 36 hours on a rate
 * limit nobody could see the cause of. The site already runs Claude through
 * Imagin, so the blog uses the same vendor, with a key of its own.
 *
 * requestParams() and parse() are deliberately pure so the request shape and
 * the response handling are pinnable without a network; write() is the thin
 * transport wrapper around them (AnthropicDescriber's layout in Imagin).
 */
class AnthropicTakeaways implements TakeawaysWriter
{
    /** Three one-sentence takeaways fit in a few hundred tokens; the cap is headroom, not a target. */
    public const MAX_OUTPUT_TOKENS = 1024;

    /** Judgment call, veto ok: the count the old command asked for, and what the blog view lays out. */
    public const DEFAULT_COUNT = 3;

    public function enabled(): bool
    {
        return class_exists(\Anthropic\Client::class)
            && (bool) config('press.ai.key');
    }

    public function write(Post $post): array
    {
        $client = new \Anthropic\Client(apiKey: config('press.ai.key'));

        $message = $client->messages->create(...$this->requestParams($post));

        foreach ($message->content as $block) {
            if ($block->type === 'text') {
                return $this->parse($block->text);
            }
        }

        // A refusal or an empty turn carries no text block. Structured output
        // means a text block IS the answer, so its absence is a failed post,
        // not an empty list to store.
        throw new RuntimeException('The model returned no text (stop reason: ' . ($message->stopReason ?? 'unknown') . ').');
    }

    /**
     * The full request, as named-argument params for messages->create.
     * Structured output guarantees a parseable JSON object -- the old command
     * asked prose instructions ("do not wrap the array") for the same thing.
     *
     * @return array<string, mixed>
     */
    public function requestParams(Post $post): array
    {
        $count = $this->count();

        return [
            'model' => config('press.ai.model') ?: 'claude-opus-5',
            'maxTokens' => self::MAX_OUTPUT_TOKENS,
            'system' => 'You write the "Key Takeaways" list printed under a blog post on a boat '
                . "manufacturer's website. Each takeaway is one complete sentence a reader "
                . 'could act on or remember, in the post\'s own terms, specific to this post. '
                . 'Do not use Oxford commas.',
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $this->prompt($post, $count),
                ],
            ],
            'outputConfig' => [
                'format' => [
                    'type' => 'json_schema',
                    'schema' => [
                        'type' => 'object',
                        'properties' => [
                            'takeaways' => [
                                'type' => 'array',
                                'items' => ['type' => 'string'],
                            ],
                        ],
                        'required' => ['takeaways'],
                        'additionalProperties' => false,
                    ],
                ],
            ],
        ];
    }

    /**
     * The user turn: the count, the title, and the post body as stored (the
     * rendered HTML -- the old command sent the same, and the model reads it
     * fine; stripping tags would drop the headings that structure it).
     */
    protected function prompt(Post $post, int $count): string
    {
        return "Give me {$count} key takeaways for this blog post.\n\n"
            . 'Title: ' . ($post->title ?? '') . "\n\n"
            . (string) $post->body;
    }

    /**
     * Structured output means the text block IS the JSON document. Only
     * non-empty strings count; the list is capped at the configured count.
     * Garbage degrades to an empty list, which the command records as a
     * failure -- never as three empty bullets on the page.
     *
     * @return list<string>
     */
    public function parse(string $json): array
    {
        $decoded = json_decode($json, true);

        if (! is_array($decoded) || ! isset($decoded['takeaways']) || ! is_array($decoded['takeaways'])) {
            return [];
        }

        $takeaways = array_values(array_filter(
            array_map(fn ($t) => is_string($t) ? trim($t) : '', $decoded['takeaways']),
            fn ($t) => $t !== ''
        ));

        return array_slice($takeaways, 0, $this->count());
    }

    protected function count(): int
    {
        // `?:` not a config default: a published block whose key resolves to null (an unset env) must still mean the default.
        return max(1, (int) (config('press.ai.takeaways') ?: self::DEFAULT_COUNT));
    }
}

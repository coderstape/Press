<?php

namespace coderstape\Press\Tests;

use PHPUnit\Framework\Attributes\Test;
use coderstape\Press\Ai\AnthropicTakeaways;
use coderstape\Press\Post;

/**
 * The shipped driver's pinnable halves: requestParams() (the exact request
 * shape sent to the API -- model, structured-output schema, prompt assembly)
 * and parse() (response handling). Both are pure, so no network and no SDK
 * client are involved; the suite skips only where the suggested dependency
 * isn't installed (it is in require-dev, Imagin's precedent).
 */
class AnthropicTakeawaysTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! class_exists(\Anthropic\Client::class)) {
            $this->markTestSkipped('anthropic-ai/sdk not installed (suggested dependency).');
        }
    }

    #[Test]
    public function enabled_requires_both_the_sdk_and_a_key()
    {
        config(['press.ai.key' => null]);
        $this->assertFalse((new AnthropicTakeaways())->enabled());

        config(['press.ai.key' => '']);
        $this->assertFalse((new AnthropicTakeaways())->enabled());

        config(['press.ai.key' => 'sk-test']);
        $this->assertTrue((new AnthropicTakeaways())->enabled());
    }

    #[Test]
    public function request_params_carry_the_model_the_schema_and_the_post()
    {
        config(['press.ai.model' => null, 'press.ai.takeaways' => null]);
        $post = new Post(['title' => 'Charging two batteries', 'body' => '<p>Match the charger to the boat.</p>']);

        $params = (new AnthropicTakeaways())->requestParams($post);

        // Opus 5 unless the site says otherwise (press.ai.model).
        $this->assertSame('claude-opus-5', $params['model']);
        $this->assertSame(AnthropicTakeaways::MAX_OUTPUT_TOKENS, $params['maxTokens']);

        // The house voice survives the port.
        $this->assertStringContainsString('Do not use Oxford commas.', $params['system']);

        // Structured output: an object with one array of strings, nothing else.
        $schema = $params['outputConfig']['format']['schema'];
        $this->assertSame('json_schema', $params['outputConfig']['format']['type']);
        $this->assertSame(['takeaways'], $schema['required']);
        $this->assertSame('string', $schema['properties']['takeaways']['items']['type']);
        $this->assertFalse($schema['additionalProperties']);

        // One user turn: the count, the title and the stored body as-is.
        $this->assertCount(1, $params['messages']);
        $this->assertSame('user', $params['messages'][0]['role']);
        $prompt = $params['messages'][0]['content'];
        $this->assertStringContainsString('Give me 3 key takeaways', $prompt);
        $this->assertStringContainsString('Title: Charging two batteries', $prompt);
        $this->assertStringContainsString('<p>Match the charger to the boat.</p>', $prompt);
    }

    #[Test]
    public function the_site_can_pick_the_model_and_the_count()
    {
        config(['press.ai.model' => 'claude-haiku-4-5', 'press.ai.takeaways' => 5]);

        $params = (new AnthropicTakeaways())->requestParams(new Post(['title' => 't', 'body' => 'b']));

        $this->assertSame('claude-haiku-4-5', $params['model']);
        $this->assertStringContainsString('Give me 5 key takeaways', $params['messages'][0]['content']);
    }

    #[Test]
    public function parse_handles_clean_output_and_degrades_on_garbage()
    {
        config(['press.ai.takeaways' => 3]);
        $driver = new AnthropicTakeaways();

        $this->assertSame(
            ['One.', 'Two.', 'Three.'],
            $driver->parse('{"takeaways":["One.","  Two. ","Three."]}')
        );

        // More than asked for is cut to the count; blanks and non-strings are dropped first.
        $this->assertSame(
            ['A', 'B', 'C'],
            $driver->parse('{"takeaways":["A","","B",7,null,"C","D"]}')
        );

        // Fewer is fewer -- the command decides whether that is worth storing.
        $this->assertSame(['Only one.'], $driver->parse('{"takeaways":["Only one."]}'));

        // Not JSON, wrong key, wrong type: an empty list, never an exception.
        $this->assertSame([], $driver->parse('not json'));
        $this->assertSame([], $driver->parse('["bare","array"]'));
        $this->assertSame([], $driver->parse('{"takeaways":"one string"}'));
        $this->assertSame([], $driver->parse('{"other":["x"]}'));
    }
}

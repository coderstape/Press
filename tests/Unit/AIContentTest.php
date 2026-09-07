<?php

namespace coderstape\Press\Tests;

use PHPUnit\Framework\Attributes\Test;
use coderstape\Press\AIContent;

class AIContentTest extends TestCase
{
    /**
     * ★ Production rows are a JSON STRING inside the json column (the writer
     * stores the model's JSON text through the cast), so the attribute comes
     * back as a string. The helper reads that shape and a plain array alike.
     */
    #[Test]
    public function takeaways_reads_the_double_encoded_production_shape_and_a_plain_array()
    {
        $stored = new AIContent(['data' => "[\n  \"First.\",\n  \"Second.\"\n]"]);
        $this->assertSame("[\n  \"First.\",\n  \"Second.\"\n]", $stored->data);
        $this->assertSame(['First.', 'Second.'], $stored->takeaways());

        $plain = new AIContent(['data' => ['One', 'Two', 'Three']]);
        $this->assertSame(['One', 'Two', 'Three'], $plain->takeaways());
    }

    #[Test]
    public function takeaways_is_an_empty_list_for_anything_else()
    {
        $this->assertSame([], (new AIContent(['data' => 'not json']))->takeaways());
        $this->assertSame([], (new AIContent(['data' => '"a string"']))->takeaways());
        $this->assertSame([], (new AIContent(['data' => null]))->takeaways());
        $this->assertSame(['kept'], (new AIContent(['data' => ['kept', '', 4, null]]))->takeaways());
    }
}

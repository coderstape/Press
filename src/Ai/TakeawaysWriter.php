<?php

namespace coderstape\Press\Ai;

use coderstape\Press\Post;

/**
 * The AI provider seam for key takeaways (v1.1.0). Press talks to exactly
 * this interface; the shipped AnthropicTakeaways is one implementation and
 * any other provider plugs in by implementing it and being named in
 * press.ai.driver. Nothing outside this namespace may know which vendor is
 * behind the seam -- the same contract shape Imagin locked for its
 * Describer, so the two packages read alike on the site.
 */
interface TakeawaysWriter
{
    /**
     * Whether this driver can actually run (its SDK is installed, its key is
     * configured). False means "feature absent": the command reports it and
     * writes nothing, it does not treat a missing key as a provider error.
     */
    public function enabled(): bool;

    /**
     * The takeaways for one post, as a list of plain sentences, in order.
     * Implementations may throw on transport/provider errors; the command
     * owns the isolation (one post's failure never ends the run).
     *
     * @return list<string>
     */
    public function write(Post $post): array;
}

<?php

namespace coderstape\Press;

use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * ═══════════════════════════════════════════════════════════════════════════
 * ★★ THE AI-GENERATED TAIL OF A POST — key takeaways, rendered under the body.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * Lived in Sportsman as App\Models\AIContent until 2026-08-25, while the ONLY
 * thing that read it was this package: Post::contentable() morphOne'd straight
 * at a host class, and `$appends = ['author', 'contentable']` put it on every
 * serialized post. A package reaching into `App\` is the coupling helm spent
 * five releases removing; there was no reason for this one to survive it.
 *
 * ★★ THE TABLE NAME IS EXPLICIT, AND BOTH HALVES OF THAT MATTER.
 *
 *   1. Eloquent snake-cases the CLASS, not the acronym: `AIContent` derives
 *      `a_i_contents`, with the underscore between the A and the I. Guessing
 *      `ai_contents` and finding no table is a real mistake somebody has
 *      already made while auditing this model, and concluded from it that the
 *      model was dead. It is not: 543 rows on 2026-08-25, written every six
 *      hours by the takeaways command.
 *
 *   2. coderstape\Press\Model::getTable() PREFIXES anything it derives with
 *      config('press.prefix') — `press_` on Sportsman, which is why the posts
 *      table is `press_posts`. This table was created unprefixed by a host
 *      migration and has always been plain `a_i_contents`. Letting the base
 *      class derive it would silently point the model at `press_a_i_contents`,
 *      a table that does not exist, and the setter branch of getTable() is
 *      what stops that.
 *
 * ★ NOTHING STORES "AIContent" AS A MORPH VALUE, so this move is code-only and
 * needs no data migration. The polymorphic pair lives on THIS table and names
 * the post — `a_i_contents.contentable_type` holds `press_post` once the
 * host's alias migration lands. The direction is the whole reason the class
 * could travel on its own.
 */
class AIContent extends Model
{
    protected $table = 'a_i_contents';

    protected $guarded = [];

    protected $casts = [
        'data' => 'json',
    ];

    /**
     * The post (or anything else) these takeaways were generated for.
     */
    public function contentable(): MorphTo
    {
        return $this->morphTo();
    }
}

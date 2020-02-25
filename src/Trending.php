<?php

namespace coderstape\Press;

class Trending extends Model
{
    /**
     * @var array
     */
    protected $guarded = [];

    /**
     * Returns the Post associated with this Trending item.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function post()
    {
        return $this->belongsTo(Post::class);
    }
}

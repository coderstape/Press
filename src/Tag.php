<?php

namespace vicgonvt\LaraPress;

class Tag extends Model
{
    /**
     * @var array
     */
    protected $guarded = [];

    /**
     * Get all of the posts for this Tag.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function posts()
    {
        return $this->belongsToMany(Post::class, $this->prefix . 'post_tag');
    }
}
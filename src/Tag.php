<?php

namespace vicgonvt\LaraPress;

use vicgonvt\LaraPress\Facades\LaraPress;

class Tag extends Model
{
    /**
     * @var array
     */
    protected $guarded = [];

    /**
     * Get the fully qualified path to this tag.
     *
     * @return \Illuminate\Contracts\Routing\UrlGenerator|string
     */
    public function path()
    {
        return url(LaraPress::path() . "/tags/{$this->id}-{$this->slug}");
    }

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
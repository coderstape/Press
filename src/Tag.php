<?php

namespace coderstape\Press;

use coderstape\Press\Facades\Press;

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
        return url(Press::path() . "/tags/{$this->id}-{$this->slug}");
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

    /**
     * Get all of the posts for this Tag.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function activePosts()
    {
        return $this->belongsToMany(Post::class, $this->prefix . 'post_tag')->active();
    }
}

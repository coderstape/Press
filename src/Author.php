<?php

namespace coderstape\Press;

use coderstape\Press\Facades\Press;
use Illuminate\Support\Str;

class Author extends Model
{
    /**
     * @var array
     */
    protected $guarded = [];

    /**
     * Get the fully qualified path to this series.
     *
     * @return \Illuminate\Contracts\Routing\UrlGenerator|string
     */
    public function path()
    {
        return url(Press::path() . "/authors/{$this->id}-" . Str::slug($this->name));
    }

    /**
     * Get the posts that belong to this series.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    /**
     * Get the posts that belong to this series.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function activePosts()
    {
        return $this->hasMany(Post::class)->active();
    }
}

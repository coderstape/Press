<?php

namespace coderstape\Press;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use coderstape\Press\Database\Factories\AuthorFactory;
use coderstape\Press\Facades\Press;
use Illuminate\Support\Str;

class Author extends Model
{
    use HasFactory;

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
        return $this->hasMany(Post::class)->active()->orderBy('published_at', 'desc');
    }

    /**
     * Bind the class-based factory explicitly (the package namespace
     * doesn't match Laravel's Database\Factories convention). The
     * factory classes are autoload-dev only: ::factory() is a
     * test-time API and is never called in production.
     *
     * @return AuthorFactory
     */
    protected static function newFactory(): AuthorFactory
    {
        return AuthorFactory::new();
    }
}

<?php

namespace coderstape\Press;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use coderstape\Press\Database\Factories\SeriesFactory;
use coderstape\Press\Facades\Press;

class Series extends Model
{
    use HasFactory;

    /**
     * @var array
     */
    protected $fillable = ['slug', 'title'];

    /**
     * Get the fully qualified path to this series.
     *
     * @return \Illuminate\Contracts\Routing\UrlGenerator|string
     */
    public function path()
    {
        return url(Press::path() . "/series/{$this->id}-{$this->slug}");
    }

    /**
     * Fetches all of the slugs by a given string.
     *
     * @param $string
     *
     * @return mixed
     */
    public static function slug($string)
    {
        return self::where('slug', \Str::slug($string))->first();
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

    /**
     * Bind the class-based factory explicitly (the package namespace
     * doesn't match Laravel's Database\Factories convention). The
     * factory classes are autoload-dev only: ::factory() is a
     * test-time API and is never called in production.
     *
     * @return SeriesFactory
     */
    protected static function newFactory(): SeriesFactory
    {
        return SeriesFactory::new();
    }
}

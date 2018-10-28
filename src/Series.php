<?php

namespace vicgonvt\LaraPress;

use vicgonvt\LaraPress\Facades\LaraPress;

class Series extends Model
{
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
        return url(LaraPress::path() . "/series/{$this->id}-{$this->slug}");
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
        return self::where('slug', str_slug($string))->first();
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
}
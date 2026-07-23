<?php

namespace coderstape\Press;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use coderstape\Press\Database\Factories\TrendingFactory;

class Trending extends Model
{
    use HasFactory;

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

    /**
     * Bind the class-based factory explicitly (the package namespace
     * doesn't match Laravel's Database\Factories convention). The
     * factory classes are autoload-dev only: ::factory() is a
     * test-time API and is never called in production.
     *
     * @return TrendingFactory
     */
    protected static function newFactory(): TrendingFactory
    {
        return TrendingFactory::new();
    }
}

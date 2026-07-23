<?php

namespace coderstape\Press;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use coderstape\Press\Database\Factories\BlogFactory;
use coderstape\Press\Facades\Press;

class Blog extends Model
{
    use HasFactory;

    /**
     * @var array
     */
    protected $guarded = [];

    /**
     * Returns a fully qualified path to this blog resource.
     *
     * @return \Illuminate\Contracts\Routing\UrlGenerator|string
     */
    public function path()
    {
        return url(Press::path() . "/admin/posts/{$this->id}");
    }

    /**
     * Returns the post associated with this raw blog record.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function post()
    {
        return $this->hasOne(Post::class, 'identifier');
    }

    /**
     * Bind the class-based factory explicitly (the package namespace
     * doesn't match Laravel's Database\Factories convention). The
     * factory classes are autoload-dev only: ::factory() is a
     * test-time API and is never called in production.
     *
     * @return BlogFactory
     */
    protected static function newFactory(): BlogFactory
    {
        return BlogFactory::new();
    }
}

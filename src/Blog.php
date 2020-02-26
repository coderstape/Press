<?php

namespace coderstape\Press;

use coderstape\Press\Facades\Press;

class Blog extends Model
{
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
}

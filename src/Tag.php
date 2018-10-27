<?php

namespace vicgonvt\LaraPress;

class Tag extends Model
{
    protected $guarded = [];

    public function posts()
    {
        return $this->belongsToMany(Post::class, $this->prefix . 'post_tag');
    }
}
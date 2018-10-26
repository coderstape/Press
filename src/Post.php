<?php

namespace vicgonvt\LaraPress;

use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    protected $fillable = [
        'identifier', 'title', 'body', 'slug', 'extra', 'published_at'
    ];
}
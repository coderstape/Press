<?php

namespace vicgonvt\LaraPress;

class Post extends Model
{
    protected $guarded = [];

    protected $dates = ['published_at'];

    public function scopeActive($query)
    {
        return $query->where('active', 1);
    }

    public function series()
    {
        return $this->belongsTo(Series::class);
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class, $this->prefix . 'post_tag');
    }
}
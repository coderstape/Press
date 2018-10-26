<?php

namespace vicgonvt\LaraPress;

use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    protected $guarded = [];

    public function series()
    {
        return $this->belongsTo(Series::class);
    }
}
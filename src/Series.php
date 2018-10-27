<?php

namespace vicgonvt\LaraPress;

class Series extends Model
{
    protected $fillable = ['slug', 'title'];

    public static function slug($string)
    {
        return self::where('slug', str_slug($string))->first();
    }
}
<?php

namespace vicgonvt\LaraPress;

class Series extends Model
{
    /**
     * @var array
     */
    protected $fillable = ['slug', 'title'];

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
}
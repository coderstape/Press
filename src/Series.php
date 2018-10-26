<?php

namespace vicgonvt\LaraPress;

use Illuminate\Database\Eloquent\Model;

class Series extends Model
{
    protected $fillable = ['slug', 'title'];

    public static function slug($string)
    {
        return self::where('slug', str_slug($string))->first();
    }
}
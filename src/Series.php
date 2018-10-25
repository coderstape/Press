<?php

namespace vicgonvt\LaraPress;

use Illuminate\Database\Eloquent\Model;

class Series extends Model
{
    protected $fillable = ['slug', 'title'];
}
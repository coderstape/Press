<?php

namespace vicgonvt\LaraPress;

abstract class Migration extends \Illuminate\Database\Migrations\Migration
{
    protected $prefix;

    public function __construct()
    {
        $this->prefix = config('larapress.prefix');
    }
}

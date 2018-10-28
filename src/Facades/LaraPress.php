<?php

namespace vicgonvt\LaraPress\Facades;

use Illuminate\Support\Facades\Facade;

class LaraPress extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'LaraPress';
    }
}
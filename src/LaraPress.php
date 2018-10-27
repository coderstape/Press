<?php

namespace vicgonvt\LaraPress;

class LaraPress
{
    /**
     * Get the URI path prefix utilized by LaraPress.
     *
     * @return string
     */
    public static function path()
    {
        return config('larapress.path', '/blog');
    }
}
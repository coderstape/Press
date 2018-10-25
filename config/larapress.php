<?php

return [
    /*
    |--------------------------------------------------------------------------
    | LaraPress Source Driver
    |--------------------------------------------------------------------------
    |
    | LaraPress allows you to select a driver that will be used for storing
    | the blog posts. By default, the file driver is used, however, other
    | drivers are available, or write your own custom driver to suite.
    |
    | Supported: "file", "database"
    |
    */

    'driver' => 'file',

    /*
    |--------------------------------------------------------------------------
    | File Options
    |--------------------------------------------------------------------------
    |
    | Here you can specify any configuration options that should be used with
    | the file driver.
    |
    */

    'file' => [
        'path' => 'blogs',
    ],
];
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
    | File Driver Options
    |--------------------------------------------------------------------------
    |
    | Here you can specify any configuration options that should be used with
    | the file driver.
    |
    */

    'file' => [
        'path' => 'blogs',
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Driver Options
    |--------------------------------------------------------------------------
    |
    | Here you can specify any configuration options that should be used with
    | the database driver.
    |
    */

    'database' => [
        'table' => 'blogs',
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Prefix
    |--------------------------------------------------------------------------
    |
    | Adding a prefix to all of the tables used by this package avoids any
    | collisions with any existing tables you may already have for your
    | project. We have set a sensible default of 'larapress_TABLENAME'.
    |
    */

    'prefix' => 'larapress_',
];
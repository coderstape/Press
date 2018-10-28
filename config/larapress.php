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
    | Supported: "file", "database", "gist"
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
    | GitHub Gist Driver Options
    |--------------------------------------------------------------------------
    |
    | Here you can specify any configuration options that should be used with
    | the gist driver.
    |
    */

    'gist' => [
        'source' => ''
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

    /*
    |--------------------------------------------------------------------------
    | URI Address Path
    |--------------------------------------------------------------------------
    |
    | Use this path value to determine on what URI we are going to serve
    | the blog. For example, if you wanted to serve it at a differnet location
    | like www.example.com/my-blog, change the value to '/my-blog'.
    |
    */

    'path' => '/blog',

    /*
    |--------------------------------------------------------------------------
    | Custom Attributes
    |--------------------------------------------------------------------------
    |
    | Allows for customization of the blog pages.
    |
    */

    // 'theme' => '',
    'trending_limit' => 1000,
];
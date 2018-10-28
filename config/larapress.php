<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Global Blog Configs
    |--------------------------------------------------------------------------
    |
    | In this section we are going to configure your blog with all of the
    | information pertaining to you and your blog. This information is
    | used throughout for meta tags, page titles and other things.
    |
    */

    'blog' => [
        'title' => 'My LaraPress Blog',
        'site_name' => 'My LaraPress Blog',
        'description' => 'An elegant markdown blog powered by Laravel.',
        'author' => 'LaraPress',
        'keywords' => 'laravel, markdown, blog',
        'image' => 'path/to/logo.png',
        'copyright' => 'Copyright Information',
        'locale' => 'en_US',
    ],

    /*
    |--------------------------------------------------------------------------
    | Source Driver
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
    | the gist driver. The source can be a string or an array and should
    | correspond to each author's listing of available gist unique IDs.
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
    | Custom Theme
    |--------------------------------------------------------------------------
    |
    | Specify any directory here where LaraPress should grab its themed view
    | files from. You must implement all of the views that LaraPress calls
    | behind the scenes.
    |
    | Default: 'larapress::'
    |
    */

    // 'theme' => '',

    /*
    |--------------------------------------------------------------------------
    | Trending Limit
    |--------------------------------------------------------------------------
    |
    | When fetching the trendings for the blog post, you can limit how many
    | visits you would like to have LaraPress use to calculate the trends.
    | Depending on your blog's popularity, this number may need to be
    | adjusted.
    |
    */

    'trending_limit' => 1000,
];
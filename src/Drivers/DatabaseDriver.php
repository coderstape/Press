<?php

namespace vicgonvt\LaraPress\Drivers;

use Illuminate\Support\Facades\Schema;
use vicgonvt\LaraPress\Blog;
use vicgonvt\LaraPress\Exceptions\DatabaseTableNotFoundException;

class DatabaseDriver extends Driver
{
    public function fetchPosts()
    {
        $blogs = Blog::all();

        $blogs->each(function ($blog) {
            $this->parse($blog->data, $blog->id);
        });

        return $this->posts;
    }

    protected function setConfig()
    {
        $this->config = config('larapress.database');
    }

    protected function validateSource()
    {
        if ( ! Schema::hasTable($this->config['table'])) {
            throw new DatabaseTableNotFoundException('Unable to find the table \'' . $this->config['table'] . '\' in your database. Please publish the database migration and run php artisan migrate.');
        }
    }
}
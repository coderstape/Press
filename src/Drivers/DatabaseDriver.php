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
        if ( ! Schema::hasTable($this->tablePrefix() . $this->config['table'])) {
            throw new DatabaseTableNotFoundException(
                'The \'' . $this->tablePrefix() . $this->config['table'] . '\' table was not found in your database. ' .
                'Run \'php artisan migrate\' to create it.'
            );
        }
    }

    /**
     * Table prefix used.
     *
     * @return \Illuminate\Config\Repository|mixed
     */
    protected function tablePrefix()
    {
        return config('larapress.prefix');
    }
}
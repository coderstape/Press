<?php

namespace vicgonvt\LaraPress\Drivers;

use Illuminate\Support\Facades\Schema;
use vicgonvt\LaraPress\Blog;
use vicgonvt\LaraPress\Exceptions\DatabaseTableNotFoundException;

class DatabaseDriver extends Driver
{
    /**
     * Fetch and parse all of the posts for the given source.
     *
     * @return mixed
     */
    public function fetchPosts()
    {
        $blogs = Blog::all();

        $blogs->each(function ($blog) {
            $this->parse($blog->data, $blog->id);
        });

        return $this->posts;
    }

    /**
     * Perform any validation necessary to assert source is valid.
     *
     * @return bool|void
     * @throws \vicgonvt\LaraPress\Exceptions\DatabaseTableNotFoundException
     */
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
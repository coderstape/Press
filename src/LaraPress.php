<?php

namespace vicgonvt\LaraPress;

use vicgonvt\LaraPress\Actions\Database;

class LaraPress
{
    /**
     * Get the URI path prefix.
     *
     * @return string
     */
    public static function path()
    {
        return config('larapress.path', '/blog');
    }

    /**
     * Check if config file has been set.
     *
     * @return bool
     */
    public static function configNotPublished()
    {
        return is_null(config('larapress'));
    }

    /**
     * Get an instance of the set driver.
     *
     * @return mixed
     */
    public static function driver()
    {
        $driver = title_case(config('larapress.driver', 'file'));
        $class = 'vicgonvt\LaraPress\Drivers\\' . $driver . 'Driver';

        return new $class;
    }

    /**
     * Get an instance of database class.
     *
     * @return \vicgonvt\LaraPress\Actions\Database
     */
    public static function database()
    {
        return new Database();
    }

    /**
     * Returns a collection of tending posts.
     *
     * @param null $limit
     *
     * @return mixed
     */
    public static function trending($limit = null)
    {
        $trending = Trending::orderBy('id', 'desc')
            ->groupBy('post_id')
            ->limit(config('larapress.trending_limit'))
            ->get();

        return ($limit) ? $trending->take($limit) : $trending;
    }
}
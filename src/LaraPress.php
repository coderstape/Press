<?php

namespace vicgonvt\LaraPress;

use vicgonvt\LaraPress\Actions\Database;

class LaraPress
{
    /**
     * @var array
     */
    protected $meta = [];

    /**
     * LaraPress constructor.
     */
    public function __construct()
    {
        $this->meta = config('larapress.blog');

        $this->meta['url'] = url(config('larapress.path'));
    }

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

    /**
     * Accepts two types of parameters. If an array is passed in, it will merge it with the
     * existing meta array. If a string is passed in, then it will return the value stored
     * at the given key.
     *
     * @param $attribute
     *
     * @return array|mixed|string
     */
    public function meta($attribute)
    {
        if (is_array($attribute)) {
            return $this->meta = array_merge($this->meta, $attribute);
        }

        if (is_a($attribute, Post::class)) {
            return $this->meta = array_merge($this->meta, [
                'title' => $attribute->title,
                'description' => $attribute->extra('description'),
                'keywords' => $attribute->extra('keywords'),
                'image' => $attribute->extra('img'),
                'url' => $attribute->path(),
            ]);
        }

        return (isset($this->meta[$attribute])) ? $this->meta[$attribute] : '';
    }
}
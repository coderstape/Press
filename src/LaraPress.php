<?php

namespace vicgonvt\LaraPress;

use ReflectionClass;
use vicgonvt\LaraPress\Actions\Database;

class LaraPress
{
    /**
     * @var array
     */
    protected $meta = [];

    /**
     * @var array
     */
    protected $fields = [];

    /**
     * LaraPress constructor.
     */
    public function __construct()
    {
        $this->meta = config('larapress.blog');

        $this->meta['url'] = url(config('larapress.path'));
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
     * Get the URI path prefix.
     *
     * @return string
     */
    public function path()
    {
        return config('larapress.path', '/blog');
    }

    /**
     * Accepts three types of parameters. If an array is passed in, it will merge it with the
     * existing meta array. If a string is passed in, then it will return the value stored
     * at the given key. If an object is passed in, it will attempt to new up a class
     * of matching name in the Transformers namespace and call transform() on it.
     *
     * @param $attributes
     *
     * @return array|mixed|string
     * @throws \ReflectionException
     */
    public function meta($attributes)
    {
        if (is_array($attributes)) {
            return $this->meta = array_merge($this->meta, $attributes);
        }

        if (is_object($attributes)) {
            $class = 'vicgonvt\LaraPress\Transformers\\' . (new ReflectionClass($attributes))->getShortName();

            if ( ! class_exists($class) && ! method_exists($class, 'transform')) {
                return;
            }

            return $this->meta = array_merge($this->meta, (new $class)->transform($attributes));
        }

        return (isset($this->meta[$attributes])) ? $this->meta[$attributes] : '';
    }

    /**
     * Bootstrap Field parsers.
     *
     * @param array $fields
     */
    public function fields(array $fields)
    {
        $this->fields = array_merge($this->fields, $fields);
    }

    /**
     * Get the available fields.
     *
     * @return array
     */
    public function availableFields()
    {
        return $this->fields;
    }
}
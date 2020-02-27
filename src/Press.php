<?php

namespace coderstape\Press;

use ReflectionClass;
use coderstape\Press\Actions\Database;

class Press
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
     * @var array
     */
    protected $editors = [];

    /**
     * Press constructor.
     */
    public function __construct()
    {
        $this->meta = config('press.blog');

        $this->meta['url'] = url(config('press.path'));
    }

    /**
     * Check if config file has been set.
     *
     * @return bool
     */
    public static function configNotPublished()
    {
        return is_null(config('press'));
    }

    /**
     * Get an instance of the set driver.
     *
     * @return mixed
     */
    public static function driver()
    {
        $driver = \Str::title(config('press.driver', 'file'));
        $class = "coderstape\\Press\\Drivers\\{$driver}Driver";

        return new $class;
    }

    /**
     * Get an instance of database class.
     *
     * @return \coderstape\Press\Actions\Database
     */
    public static function database()
    {
        return new Database();
    }

    /**
     * Processes any posts in the current driver.
     *
     * @return bool
     */
    public function process()
    {
        $posts = $this->driver()->fetchPosts();

        return !! $posts && $this->database()->savePosts($posts);
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
            ->limit(config('press.trending_limit'))
            ->whereHas('post')
            ->with('post')
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
        return config('press.path', '/blog');
    }

    /**
     * Get the URI path prefix.
     *
     * @return string
     */
    public function pagination()
    {
        return config('press.pagination', '15');
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
    public function meta($attributes = null)
    {
        if (is_null($attributes)) {
            return $this->meta;
        }

        if (is_array($attributes)) {
            return $this->meta = array_merge($this->meta, $attributes);
        }

        if (is_object($attributes)) {
            $class = 'coderstape\\Press\\Transformers\\' . (new ReflectionClass($attributes))->getShortName();

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

    /**
     * Bootstrap editors.
     *
     * @param array $editors
     */
    public function editors(array $editors)
    {
        $this->editors = array_merge($this->editors, $editors);
    }

    /**
     * Currently authenticated user is an editor.
     *
     * @return boolean
     */
    public function isEditor()
    {
        if (auth()->guest()) {
            return false;
        }

        return in_array(auth()->user()->email, $this->editors);
    }
}

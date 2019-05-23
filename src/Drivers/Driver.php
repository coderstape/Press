<?php

namespace coderstape\Press\Drivers;

use coderstape\Press\PressFileParser;

abstract class Driver
{
    /**
     * @var
     */
    protected $config;

    /**
     * @var
     */
    protected $posts;

    /**
     * Driver constructor.
     */
    public function __construct()
    {
        $this->setConfig();
        $this->validateSource();
    }

    /**
     * Fetch and parse all of the posts for the given source.
     */
    public abstract function fetchPosts();

    /**
     * Instantiates the PressFile parser and builds up an array of posts.
     *
     * @param $content
     * @param $identifier
     */
    protected function parse($content, $identifier)
    {
        $this->posts[] = array_merge(
            (new PressFileParser($content))->getData(),
            ['identifier' => str_slug($identifier)]
        );
    }

    /**
     * Fetch the appropriate config array for this source.
     */
    protected function setConfig()
    {
        $this->config = config('press.' . config('press.driver'));
    }

    /**
     * Perform any validation necessary to assert source is valid.
     *
     * @return bool|void
     */
    protected function validateSource()
    {
        return true;
    }
}

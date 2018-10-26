<?php

namespace vicgonvt\LaraPress\Drivers;

use vicgonvt\LaraPress\PressFileParser;

abstract class Driver
{
    protected $config;
    protected $posts;

    public function __construct()
    {
        $this->setConfig();
        $this->validateSource();
    }

    public abstract function fetchPosts();

    protected function parse($content, $identifier)
    {
        $this->posts[] = array_merge(
            (new PressFileParser($content))->getData(),
            ['identifier' => str_slug($identifier)]
        );
    }

    protected function setConfig()
    {
        //
    }

    protected function validateSource()
    {
        //
    }
}

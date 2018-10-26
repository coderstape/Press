<?php

namespace vicgonvt\LaraPress\Drivers;

interface Driver
{
    public function fetchPosts();

    public function parse($content, $filename);

    public function setConfig();

    public function validateSource();
}
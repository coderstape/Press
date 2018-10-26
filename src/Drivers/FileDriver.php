<?php

namespace vicgonvt\LaraPress\Drivers;

use Illuminate\Support\Facades\File;
use vicgonvt\LaraPress\PressFileParser;

class FileDriver implements Driver
{
    protected $config;

    protected $posts;

    public function __construct()
    {
        $this->setConfig();
        $this->validateSource();
    }

    public function fetchPosts()
    {
        $files = File::files($this->config['path']);

        foreach ($files as $file) {
            $this->parse($file->getContents(), $file->getFilename());
        }

        return $this->posts;
    }

    public function parse($content, $filename)
    {
        $this->posts[] = array_merge(
            (new PressFileParser($content))->getData(),
            ['identifier' => str_slug($filename)]
        );
    }

    public function setConfig()
    {
        $this->config = config('larapress.file');
    }

    public function validateSource()
    {
        if ( ! File::exists($this->config['path'])) {
            throw new \Exception('Directory at ' . $this->config['path'] . ' does not exist.');
        }
    }
}
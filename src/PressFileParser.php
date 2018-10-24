<?php

namespace vicgonvt\LaraPress;

use Illuminate\Support\Facades\File;

class PressFileParser
{
    public function __construct($filename)
    {
        $this->filename = $filename;
    }

    public function parse()
    {
        return $this->splitFile();
    }

    private function splitFile()
    {
        preg_match('/^\-{3}(.*?)\-{3}(.*)/s', File::get($this->filename), $markdownArray);

        return $markdownArray;
    }
}
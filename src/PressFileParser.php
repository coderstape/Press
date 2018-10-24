<?php

namespace vicgonvt\LaraPress;

use Illuminate\Support\Facades\File;

class PressFileParser
{
    private $markdownArray;

    public function __construct($filename)
    {
        $this->filename = $filename;

        $this->splitFile();
    }

    public function parse()
    {
        return $this->markdownArray;
    }

    public function head()
    {
        foreach (explode("\n", trim($this->markdownArray[1])) as $fieldString) {
            $fieldString = trim($fieldString);

            preg_match('/(.*?)\:(.*)/', $fieldString, $fieldArray);

            $headArray[trim($fieldArray[1])] = trim($fieldArray[2]);
        }

        return $headArray;
    }

    private function splitFile()
    {
        preg_match('/^\-{3}(.*?)\-{3}(.*)/s', File::get($this->filename), $markdownArray);

        return $this->markdownArray = $markdownArray;
    }
}
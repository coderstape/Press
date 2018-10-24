<?php

namespace vicgonvt\LaraPress;

use Illuminate\Support\Facades\File;

class PressFileParser
{
    private $splitFile;

    private $parsedData;

    public function __construct($filename)
    {
        $this->filename = $filename;

        $this->splitFile();

        $this->parse();
    }

    public function getData()
    {
        return $this->parsedData;
    }

    protected function parse()
    {
        foreach (explode("\n", trim($this->splitFile[1])) as $fieldString) {
            $fieldString = trim($fieldString);

            preg_match('/(.*?)\:(.*)/', $fieldString, $fieldArray);

            $this->parsedData[trim($fieldArray[1])] = trim($fieldArray[2]);
        }

        $this->parsedData['body'] = trim($this->splitFile[2]);
    }

    protected function splitFile()
    {
        preg_match('/^\-{3}(.*?)\-{3}(.*)/s', File::get($this->filename), $this->splitFile);
    }
}
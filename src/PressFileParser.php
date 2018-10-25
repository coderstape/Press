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

        $this->explodeData();
        
        $this->processFields();

        $this->handleFields();
    }

    public function getData()
    {
        return $this->parsedData;
    }

    protected function processFields()
    {
        foreach ($this->parsedData as $fieldType => $fieldData) {
            $class = 'vicgonvt\LaraPress\Field\\' . ucfirst(camel_case($fieldType));

            if (class_exists($class) && method_exists($class, 'process')) {
                $this->parsedData = array_merge(
                    $this->parsedData,
                    $class::process($fieldType, $fieldData, $this->parsedData)
                );
            }
        }
    }

    protected function handleFields()
    {
        foreach ($this->parsedData as $fieldType => $fieldData) {
            $class = 'vicgonvt\LaraPress\Field\\' . ucfirst(camel_case($fieldType));

            if (class_exists($class) && method_exists($class, 'handle')) {
                $class::handle($fieldType, $fieldData);
            }
        }
    }

    protected function explodeData()
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
        preg_match(
            '/^\-{3}(.*?)\-{3}(.*)/s',
            File::exists($this->filename) ? File::get($this->filename) : $this->filename,
            $this->splitFile
        );
    }
}
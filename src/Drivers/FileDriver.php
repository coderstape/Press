<?php

namespace vicgonvt\LaraPress\Drivers;

use Illuminate\Support\Facades\File;
use vicgonvt\LaraPress\Exceptions\FileDriverDirectoryNotFoundException;

class FileDriver extends Driver
{
    public function fetchPosts()
    {
        $files = File::files($this->config['path']);

        foreach ($files as $file) {
            $this->parse($file->getContents(), $file->getFilename());
        }

        return $this->posts;
    }

    protected function setConfig()
    {
        $this->config = config('larapress.file');
    }

    protected function validateSource()
    {
        if ( ! File::exists($this->config['path'])) {
            throw new FileDriverDirectoryNotFoundException('Directory at ' . $this->config['path'] . ' does not exist.');
        }
    }
}
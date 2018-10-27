<?php

namespace vicgonvt\LaraPress\Drivers;

use Illuminate\Support\Facades\File;
use vicgonvt\LaraPress\Exceptions\FileDriverDirectoryNotFoundException;

class FileDriver extends Driver
{
    /**
     * Fetch and parse all of the posts for the given source.
     *
     * @return mixed
     */
    public function fetchPosts()
    {
        $files = File::files($this->config['path']);

        foreach ($files as $file) {
            $this->parse($file->getContents(), $file->getFilename());
        }

        return $this->posts;
    }

    /**
     * Perform any validation necessary to assert source is valid.
     *
     * @return bool|void
     * @throws \vicgonvt\LaraPress\Exceptions\FileDriverDirectoryNotFoundException
     */
    protected function validateSource()
    {
        if ( ! File::exists($this->config['path'])) {
            throw new FileDriverDirectoryNotFoundException(
                'Directory at \'' . $this->config['path'] . '\' does not exist. ' .
                'Check the directory path in the config file.'
            );
        }
    }
}
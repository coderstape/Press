<?php

namespace vicgonvt\LaraPress\Drivers;

use Zttp\Zttp;

class GistDriver extends Driver
{
    public function fetchPosts()
    {
        $sources = ( ! is_array($this->config['source']))
            ? [$this->config['source']]
            : $this->config['source'];

        foreach ($sources as $gistSource) {
            $source = $this->getGist($gistSource);

            foreach ($this->linesToArray($source) as $postGist) {
                $post = $this->getGist($postGist);

                $this->parse($post, $postGist);
            }

            return $this->posts;
        }
    }

    protected function setConfig()
    {
        $this->config = config('larapress.gist');
    }

    protected function validateSource()
    {
        return true;
    }

    private function gistsApi()
    {
        return 'https://api.github.com/gists/';
    }

    private function getGist($gistId)
    {
        $response = Zttp::get($this->gistsApi() . $gistId);

        return array_pop($response->json()['files'])['content'];
    }

    private function linesToArray($source)
    {
        return explode("\n", $source);
    }
}
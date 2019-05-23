<?php

namespace coderstape\Press\Drivers;

use Zttp\Zttp;

class GistDriver extends Driver
{
    /**
     * Fetch and parse all of the posts for the given source.
     */
    public function fetchPosts()
    {
        $sources = ( ! is_array($this->config['source']))
            ? [$this->config['source']]
            : $this->config['source'];
        $validSources = [];

        foreach ($sources as $gistSource) {
            $source = $this->getGist($gistSource);

            if ($source) {
                $validSources[] = $source;
            }
        }

        if ( ! $validSources) {
            return;
        }

        foreach ($validSources as $sourceGists) {
            $this->getSourcePosts($sourceGists);
        }

        return $this->posts;
    }

    /**
     * URI partial for GitHub Gist API.
     *
     * @return string
     */
    private function gistsApi()
    {
        return 'https://api.github.com/gists/';
    }

    /**
     * Given a Gist ID, it will fetch it and retrieve the payload.
     *
     * @param $gistId
     *
     * @return mixed
     */
    private function getGist($gistId)
    {
        $response = Zttp::get($this->gistsApi() . $gistId)->json();
        if (isset($response['message'])) {
            return false;
        }

        return array_pop($response['files'])['content'];
    }

    /**
     * Separates each line into an array.
     *
     * @param $source
     *
     * @return array
     */
    private function linesToArray($source)
    {
        return explode("\n", $source);
    }

    /**
     * Takes an array of gists and parses each one.
     *
     * @param $sourceGists
     */
    protected function getSourcePosts($sourceGists)
    {
        foreach ($this->linesToArray($sourceGists) as $gist) {
            if ($post = $this->getGist($gist)) {
                $this->parse($post, $gist);
            }
        }
    }
}
<?php

namespace vicgonvt\LaraPress\Transformers;

class Tag implements Transformer
{
    /**
     * Transform a tag model for setting proper meta tags on the view.
     *
     * @param $tag
     *
     * @return array
     */
    public function transform($tag)
    {
        return [
            'title' => $tag->name,
            'description' => 'Showing all posts associated with the tag ' . $tag->name,
            'keywords' => str_replace(' ', ', ', $tag->name),
            'url' => $tag->path(),
        ];
    }
}
<?php

namespace coderstape\Press\Transformers;

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
            'description' => 'Now showing only posts that are associated and filtered by the tag ' . $tag->name,
            'keywords' => str_replace(' ', ', ', $tag->name),
            'url' => $tag->path(),
        ];
    }
}

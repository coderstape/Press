<?php

namespace vicgonvt\LaraPress\Transformers;

class Post implements Transformer
{
    /**
     * Transform a post model for setting proper meta tags on the view.
     *
     * @param $post
     *
     * @return array
     */
    public function transform($post)
    {
        return [
            'title' => $post->title,
            'description' => $post->extra('description'),
            'keywords' => $post->extra('keywords'),
            'image' => $post->extra('img'),
            'url' => $post->path(),
        ];
    }
}
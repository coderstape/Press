<?php

namespace vicgonvt\LaraPress\Transformers;

class Post implements Transformer
{
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
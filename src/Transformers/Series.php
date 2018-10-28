<?php

namespace vicgonvt\LaraPress\Transformers;

class Series implements Transformer
{
    /**
     * Transform a series model for setting proper meta tags on the view.
     *
     * @param $series
     *
     * @return array
     */
    public function transform($series)
    {
        return [
            'title' => $series->title,
            'description' => 'Showing all posts in the series titled ' . $series->title,
            'keywords' => str_replace(' ', ', ', $series->title),
            'url' => $series->path(),
        ];
    }
}
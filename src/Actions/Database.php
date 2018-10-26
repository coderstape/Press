<?php

namespace vicgonvt\LaraPress\Actions;

use vicgonvt\LaraPress\Post;
use vicgonvt\LaraPress\Series;

class Database
{
    public function savePosts($posts)
    {
        foreach ($posts as $post) {

            $series = (isset($post['series'])) ? Series::slug($post['series']) : null;

            Post::create([
                'identifier' => $post['identifier'],
                'slug' => $post['slug'],
                'title' => $post['title'],
                'body' => $post['body'],
                'extra' => $post['extra'],
                'series_id' => ($series) ? $series->id : null,
                'published_at' => $post['published_at'],
            ]);

        }

        return true;
    }
}
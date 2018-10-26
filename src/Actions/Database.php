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

            Post::updateOrCreate(
                ['identifier' => $post['identifier']],
                [
                    'slug' => $post['slug'],
                    'title' => $post['title'],
                    'body' => $post['body'],
                    'extra' => $post['extra'],
                    'series_id' => ($series) ? $series->id : null,
                    'published_at' => $post['published_at'],
                ]
            );

        }

        $this->cleanPosts(array_pluck($posts, 'identifier'));
        $this->cleanSeries(array_pluck($posts, 'series'));

        return true;
    }

    protected function cleanPosts($identifiers)
    {
        return Post::whereNotIn('identifier', $identifiers)
            ->get()
            ->each(function ($post) {
                $post->active = 0;
                $post->save();
            });
    }

    protected function cleanSeries($series)
    {
        $series = array_map(function ($series) {
            return str_slug($series);
        }, $series);

        return Series::whereNotIn('slug', $series)
            ->get()
            ->each(function ($series) {
                $series->delete();
            });
    }
}
<?php

namespace vicgonvt\LaraPress\Actions;

use vicgonvt\LaraPress\Post;
use vicgonvt\LaraPress\Series;
use vicgonvt\LaraPress\Tag;

class Database
{
    /**
     * Takes an array of posts and persists them to the database.
     *
     * @param $posts
     *
     * @return bool
     */
    public function savePosts($posts)
    {
        foreach ($posts as $post) {

            Post::updateOrCreate(
                ['identifier' => $post['identifier']],
                [
                    'slug' => $post['slug'],
                    'title' => $post['title'],
                    'body' => $post['body'],
                    'extra' => $post['extra'],
                    'series_id' => (isset($post['series_id'])) ? $post['series_id'] : null,
                    'published_at' => $post['published_at'],
                ]
            )->tags()->sync($post['tag_ids']);
        }

        $this->cleanPosts(array_pluck($posts, 'identifier'));
        $this->cleanSeries(array_pluck($posts, 'series'));
        $this->cleanTags();

        return true;
    }

    /**
     * Takes an array of identifiers and deactivates any posts not in the given array.
     *
     * @param $identifiers
     *
     * @return mixed
     */
    protected function cleanPosts($identifiers)
    {
        return Post::whereNotIn('identifier', $identifiers)
            ->get()
            ->each(function ($post) {
                $post->active = 0;
                $post->save();
            });
    }

    /**
     * Finds all unused series in the database and deletes them.
     *
     * @param $series
     *
     * @return mixed
     */
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

    /**
     * Finds all tags not being used by a post and deletes them.
     *
     * @return mixed
     */
    protected function cleanTags()
    {
        return Tag::doesntHave('posts')
            ->get()
            ->each(function ($tag) {
                $tag->delete();
            });
    }
}

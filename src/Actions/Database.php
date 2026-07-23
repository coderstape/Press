<?php

namespace coderstape\Press\Actions;

use coderstape\Press\Post;
use coderstape\Press\Series;
use coderstape\Press\Tag;

class Database
{
    public function savePost($post)
    {
        return Post::updateOrCreate(
            ['identifier' => $post['identifier']],
            [
                'slug' => $post['slug'],
                'title' => $post['title'],
                'body' => $post['body'],
                'extra' => $post['extra'] ?? json_encode([]),
                'series_id' => $post['series_id'] ?? null,
                'author_id' => $post['author_id'] ?? null,
                // Defaults are judgment values (veto ok): a post with
                // no date head publishes immediately (matches the
                // posts.active column default of 1); no tags head
                // syncs to none. Hard indexes here used to crash
                // ingest for any post missing an optional head.
                'active' => $post['active'] ?? 1,
                'published_at' => $post['published_at'] ?? now(),
            ]
        )->tags()->sync($post['tag_ids'] ?? []);
    }

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
            $this->savePost($post);
        }

        $this->cleanPosts(\Arr::pluck($posts, 'identifier'));
        // Head keys keep their authored casing ('series' vs 'Series' --
        // the parser accepts both), so a case-sensitive pluck missed
        // capitalized heads and deleted their series as "unused" on the
        // next run. Pinned in DatabaseTest.
        $this->cleanSeries(array_map(function ($post) {
            return $post['series'] ?? $post['Series'] ?? null;
        }, $posts));
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
            return \Str::slug($series);
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

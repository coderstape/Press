<?php

namespace vicgonvt\LaraPress\Actions;

use vicgonvt\LaraPress\Post;

class Database
{
    public function savePosts($posts)
    {
        foreach ($posts as $post) {

            Post::create([
                'identifier' => $post['identifier'],
                'slug' => $post['slug'],
                'title' => $post['title'],
                'body' => $post['body'],
                'extra' => $post['extra'],
                'published_at' => $post['published_at'],
            ]);

        }

        return true;
    }
}
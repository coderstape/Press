<?php

namespace vicgonvt\LaraPress\Http\Controllers;

use Illuminate\Routing\Controller;
use vicgonvt\LaraPress\Post;

class PostController extends Controller
{
    /**
     * List all of the active posts.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index()
    {
        $posts = Post::active()->get();

        return theme('posts.index', compact('posts'));
    }

    /**
     * Show a given post.
     *
     * @param $post
     * @param $slug
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function show($post, $slug)
    {
        $post = Post::active()->with(['tags', 'series'])->whereId($post)->whereSlug($slug)->first();

        return theme('posts.show', compact('post'));
    }
}
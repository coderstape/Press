<?php

namespace coderstape\Press\Http\Controllers;

use coderstape\Press\Series;
use Illuminate\Routing\Controller;
use coderstape\Press\Facades\Press;
use coderstape\Press\Post;

class PostController extends Controller
{
    /**
     * List all of the active posts.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index()
    {
        $posts = Post::active()->orderBy('published_at', 'DESC')->paginate(Press::pagination());

        $series = Series::orderBy('title')->with('posts')->get();

        return theme('posts.index', compact('posts', 'series'));
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

        $post->recordVisit();
        Press::meta($post);

        $series = Series::orderBy('title')->with('posts')->get();

        return theme('posts.show', compact('post', 'series'));
    }
}

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
        $posts = Post::active()->orderBy('published_at', 'DESC');

        if (request('search')) {
            $posts->where('title', 'LIKE', '%' . request('search').'%')
                ->orWhere('body', 'LIKE', '%' . request('search').'%');
        }

        $series = Series::orderBy('title')->with('posts')->get();
        $posts = $posts->paginate(Press::pagination());

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
        if (request()->has('preview')) {
            $post = Post::with(['tags', 'series'])->whereId($post)->whereSlug($slug)->firstOrFail();
        } else {
            $post = Post::active()->with(['tags', 'series'])->whereId($post)->whereSlug($slug)->firstOrFail();
        }

        $post->recordVisit();
        Press::meta($post);

        $series = Series::orderBy('title')->with('posts')->get();

        return theme('posts.show', compact('post', 'series'));
    }
}

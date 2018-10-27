<?php

namespace vicgonvt\LaraPress\Http\Controllers;

use Illuminate\Routing\Controller;
use vicgonvt\LaraPress\Post;

class PostController extends Controller
{
    public function index()
    {
        $posts = Post::active()->get();

        return view('larapress::posts.index', compact('posts'));
    }

    public function show($post, $slug)
    {
        $post = Post::active()->whereId($post)->whereSlug($slug)->get();

        return view('larapress::posts.show', compact('post'));
    }
}
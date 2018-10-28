<?php

namespace vicgonvt\LaraPress\Http\Controllers;

use Illuminate\Routing\Controller;
use vicgonvt\LaraPress\Post;
use vicgonvt\LaraPress\Tag;

class TagController extends Controller
{
    /**
     * List all of the tags.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index()
    {
        $tags = Tag::whereHas('posts')->with('posts')->get();

        return view('larapress::tags.index', compact('tags'));
    }

    /**
     * Show a given tag.
     *
     * @param $tag
     * @param $slug
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function show($tag, $slug)
    {
        $tag = Tag::with('posts')->whereId($tag)->whereSlug($slug)->first();

        return view('larapress::tags.show', compact('tag'));
    }
}
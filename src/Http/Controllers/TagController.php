<?php

namespace vicgonvt\LaraPress\Http\Controllers;

use Illuminate\Routing\Controller;
use vicgonvt\LaraPress\Facades\LaraPress;
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

        return theme('tags.index', compact('tags'));
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

        LaraPress::meta($tag);

        return theme('tags.show', compact('tag'));
    }
}
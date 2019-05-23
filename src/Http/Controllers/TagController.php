<?php

namespace coderstape\Press\Http\Controllers;

use Illuminate\Routing\Controller;
use coderstape\Press\Facades\Press;
use coderstape\Press\Tag;

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

        Press::meta($tag);

        return theme('tags.show', compact('tag'));
    }
}
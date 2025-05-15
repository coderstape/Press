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
        $tags = Tag::whereHas('activePosts')->with('activePosts')->orderBy('name')->get();

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
        $tag = Tag::whereId($tag)->whereSlug($slug)->firstOrFail();

        $posts = $tag->activePosts()->paginate(Press::pagination());

        Press::meta($tag);

        return theme('tags.show', compact('tag', 'posts'));
    }
}

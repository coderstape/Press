<?php

namespace coderstape\Press\Http\Controllers;

use coderstape\Press\Author;
use Illuminate\Routing\Controller;
use coderstape\Press\Facades\Press;
use coderstape\Press\Series;

class AuthorController extends Controller
{
    /**
     * List all of the active authors.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index()
    {
        $authors = Author::whereHas('activePosts')->orderBy('published_at', 'desc')->get();

        return theme('authors.index', compact('authors'));
    }

    /**
     * Show a given author.
     *
     * @param $authors
     * @param $slug
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function show($author, $slug)
    {
        $author = Author::whereHas('activePosts')->whereId($author)->firstOrFail();

        Press::meta($author);

        return theme('authors.show', compact('author'));
    }
}

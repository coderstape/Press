<?php

namespace coderstape\Press\Http\Controllers;

use coderstape\Press\Blog;
use coderstape\Press\Series;
use Illuminate\Routing\Controller;
use coderstape\Press\Facades\Press;
use coderstape\Press\Post;

class AdminPostController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * List all of the active posts.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index()
    {
        $posts = Post::latest()->paginate(Press::pagination());

        return theme('admin.posts.index', compact('posts'));
    }

    /**
     * Show the edit form for a given post.
     *
     * @param $post
     * @param $slug
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function edit($post, $slug)
    {
        $post = Blog::where('id', $post)
            ->first();

        Press::meta($post);

        return theme('admin.posts.edit', compact('post'));
    }

    /**
     * Update a given post.
     *
     * @param $post
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function update($post)
    {
        $data = request()->validate([
            'data' => 'required',
        ]);

        $post = Blog::where('id', $post)
            ->first();

        $post->update($data);

        return redirect()->back();
    }
}
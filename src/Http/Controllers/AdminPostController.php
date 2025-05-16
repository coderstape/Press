<?php

namespace coderstape\Press\Http\Controllers;

use coderstape\Press\Blog;
use coderstape\Press\Facades\Press;
use coderstape\Press\Post;
use Illuminate\Routing\Controller;

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
        $posts = Post::orderBy('published_at', 'DESC')->paginate(Press::pagination());

        return theme('admin.posts.index', compact('posts'));
    }

    public function store()
    {
        $data = request()->validate([
            'data' => 'required',
        ]);

        $blog = Blog::create($data);

        if (Press::process()) {
            return redirect()->to(Press::path() . '/admin/posts/'.$blog->id.'/edit');
        }

        return 'error';
    }

    /**
     * Show the edit form for a given post.
     *
     * @param $post
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function edit($post)
    {
        $post = Blog::where('id', $post)
            ->firstOrFail();

        Press::meta($post);

        return theme('admin.posts.edit', compact('post'));
    }

    /**
     * Create a new post.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function create()
    {
        return theme('admin.posts.create');
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
            ->firstOrFail();

        $post->update($data);

        Press::database()->savePost(Press::driver()->parse($post->data, $post->id));

        return redirect()->back();
    }
}

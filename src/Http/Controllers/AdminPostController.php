<?php

namespace coderstape\Press\Http\Controllers;

use coderstape\Press\Blog;
use coderstape\Press\Facades\Press;
use coderstape\Press\Http\Middleware\EnsureUserIsEditor;
use coderstape\Press\Post;
use Illuminate\Routing\Controller;

class AdminPostController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');

        // Authoring is editor-only. 'auth' ALONE let any registered
        // user create and edit posts, and post bodies reach the
        // public blog through {!! !!} on the consuming site with
        // Parsedown's raw-HTML passthrough intact -- so bare
        // authentication was arbitrary markup on the public blog from
        // any account. Registration order matters: 'auth' first, so a
        // guest gets the login redirect rather than a 403 they could
        // never clear by signing in.
        $this->middleware(EnsureUserIsEditor::class);
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

<?php

namespace coderstape\Press\Http\Controllers;

use coderstape\Press\Series;
use Illuminate\Database\Eloquent\Builder;
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
                ->orWhere('body', 'LIKE', '%' . request('search').'%')
                ->orWhereHas('author', function (Builder $query) {
                    $query->where('name', 'LIKE', '%'.request('search').'%');
                });
        }

        if (request('draft')) {
            $posts->where('active', '0');
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

        if ($tags = $post->tags->pluck('id')->toArray()) {
            $related = Post::whereHas('tags', function ($query) use ($tags) {
                $query->whereIn('id', $tags);
            })
                ->where('id', '!=', $post->id)
                ->active()
                ->inRandomOrder()
                ->limit(3)->get();
        }

        return theme('posts.show', compact('post', 'series', 'related'));
    }
}

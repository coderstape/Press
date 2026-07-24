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
            // The OR chain must be grouped, or it escapes the active()
            // constraint above and a search hit on a draft's body
            // publishes the draft. Pinned in PostControllerTest.
            $posts->where(function (Builder $query) {
                $query->where('title', 'LIKE', '%' . request('search').'%')
                    ->orWhere('body', 'LIKE', '%' . request('search').'%')
                    ->orWhereHas('author', function (Builder $query) {
                        $query->where('name', 'LIKE', '%'.request('search').'%');
                    });
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
        $preview = request()->has('preview');

        if ($preview) {
            $post = Post::with(['tags', 'series'])->whereId($post)->whereSlug($slug)->firstOrFail();
        } else {
            $post = Post::active()->with(['tags', 'series'])->whereId($post)->whereSlug($slug)->firstOrFail();
        }

        // Preview traffic isn't real traffic: a draft reviewed a dozen
        // times before publication used to arrive on the site with
        // those visits already banked in the trending table. Applies
        // to ?preview on an ALREADY-ACTIVE post too (judgment call,
        // veto ok -- an editor re-checking a live post is still not a
        // reader). This changes what a preview hit RECORDS, not who
        // may make one; whether ?preview should be gated at all
        // remains an open decision.
        if ( ! $preview) {
            $post->recordVisit();
        }

        Press::meta($post);

        $series = Series::orderBy('title')->with('posts')->get();

        // Initialized so a tag-less post doesn't hand compact() an
        // undefined variable (site-published views may read $related).
        $related = collect();

        if ($tags = $post->tags->pluck('id')->toArray()) {
            $related = Post::whereHas('tags', function ($query) use ($tags) {
                $query->whereIn('id', $tags);
            })
                ->where('id', '!=', $post->id)
                ->active()
                ->inRandomOrder()
                ->limit(4)->get();
        }

        return theme('posts.show', compact('post', 'series', 'related'));
    }
}

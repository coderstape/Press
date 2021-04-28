<?php

namespace coderstape\Press\Http\Controllers;

use Illuminate\Routing\Controller;
use coderstape\Press\Facades\Press;
use coderstape\Press\Series;

class SeriesController extends Controller
{
    /**
     * List all of the active series.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index()
    {
        $series = Series::with('activePosts')->get();

        return theme('series.index', compact('series'));
    }

    /**
     * Show a given series.
     *
     * @param $series
     * @param $slug
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function show($series, $slug)
    {
        $series = Series::with('activePosts')->whereId($series)->whereSlug($slug)->firstOrFail();

        Press::meta($series);

        return theme('series.show', compact('series'));
    }
}

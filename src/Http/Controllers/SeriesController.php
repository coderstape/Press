<?php

namespace vicgonvt\LaraPress\Http\Controllers;

use Illuminate\Routing\Controller;
use vicgonvt\LaraPress\Series;

class SeriesController extends Controller
{
    /**
     * List all of the active series.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index()
    {
        $series = Series::with('posts')->get();

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
        $series = Series::with('posts')->whereId($series)->whereSlug($slug)->first();

        return theme('series.show', compact('series'));
    }
}
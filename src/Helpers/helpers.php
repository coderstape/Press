<?php

if ( ! function_exists('theme')) {
    /**
     * Shortcut for generating view path with theme.
     *
     * @param  string $view
     * @param $data
     * @param array $mergeData
     *
     * @return string
     */
    function theme($view = null, $data = [], $mergeData = [])
    {
        return view(config('larapress.theme', 'larapress::') . '.' . $view, $data, $mergeData);
    }
}

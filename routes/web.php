<?php

Route::get('series', 'SeriesController@index');
Route::get('series/{series}-{slug}', 'SeriesController@show');

Route::get('tags', 'TagController@index');
Route::get('tags/{tag}-{slug}', 'TagController@show');

Route::get('/', 'PostController@index');
Route::get('{post}-{slug}', 'PostController@show');
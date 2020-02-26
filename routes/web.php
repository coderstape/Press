<?php

Route::get('series', 'SeriesController@index');
Route::get('series/{series}-{slug}', 'SeriesController@show');

Route::get('tags', 'TagController@index');
Route::get('tags/{tag}-{slug}', 'TagController@show');

Route::get('/', 'PostController@index');
Route::get('{post}-{slug}', 'PostController@show');

Route::get('admin', 'AdminPostController@index');
Route::get('{post}-{slug}/edit', 'AdminPostController@edit');
Route::patch('blog/{blog}', 'AdminPostController@update');

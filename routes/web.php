<?php

use coderstape\Press\Facades\Press;

Route::get('series', 'SeriesController@index');
Route::get('series/{series}-{slug}', 'SeriesController@show');

Route::get('tags', 'TagController@index');
Route::get('tags/{tag}-{slug}', 'TagController@show');

Route::get('/', 'PostController@index');
Route::get('{post}-{slug}', 'PostController@show');

Route::prefix('admin')->group(function () {
    Route::redirect('/', Press::path() . '/admin/posts');

    Route::get('/posts', 'AdminPostController@index');
    Route::post('/posts', 'AdminPostController@store');
    Route::get('/posts/create', 'AdminPostController@create');
    Route::get('/posts/{post}/edit', 'AdminPostController@edit');
    Route::patch('/posts/{post}', 'AdminPostController@update');
});

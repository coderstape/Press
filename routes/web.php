<?php

use Illuminate\Support\Facades\Route;

Route::get('posts', 'PostController@index');
Route::get('posts/{post}-{slug}', 'PostController@show');
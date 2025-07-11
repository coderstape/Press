<?php

use Carbon\Carbon;
use coderstape\Press\Post;

$factory->define(Post::class, function (Faker\Generator $faker) {
    return [
        'identifier' => \Str::random(),
        'slug' => \Str::slug($faker->sentence),
        'title' => $faker->sentence,
        'body' => $faker->paragraph,
        'extra' => json_encode(['test' => 'value', 'author' => 'Test Author']),
        'published_at' => Carbon::now(),
    ];
});
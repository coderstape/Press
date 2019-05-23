<?php

use coderstape\Press\Post;
use coderstape\Press\Trending;

$factory->define(Trending::class, function (Faker\Generator $faker) {
    return [
        'post_id' => function () {
            return (factory(Post::class)->create())->id;
        }
    ];
});
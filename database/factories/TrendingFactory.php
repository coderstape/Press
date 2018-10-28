<?php

use vicgonvt\LaraPress\Post;
use vicgonvt\LaraPress\Trending;

$factory->define(Trending::class, function (Faker\Generator $faker) {
    return [
        'post_id' => function () {
            return (factory(Post::class)->create())->id;
        }
    ];
});
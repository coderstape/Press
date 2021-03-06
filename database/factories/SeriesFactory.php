<?php

use coderstape\Press\Series;

$factory->define(Series::class, function (Faker\Generator $faker) {
    $title = $faker->sentence(4);

    return [
        'title' => $title,
        'slug' => \Str::slug($title),
    ];
});
<?php

use vicgonvt\LaraPress\Series;

$factory->define(Series::class, function (Faker\Generator $faker) {
    $title = $faker->sentence(4);

    return [
        'title' => $title,
        'slug' => str_slug($title),
    ];
});
<?php

use vicgonvt\LaraPress\Tag;

$factory->define(Tag::class, function (Faker\Generator $faker) {
    $name = $faker->sentence(4);

    return [
        'name' => $name,
        'slug' => str_slug($name),
    ];
});
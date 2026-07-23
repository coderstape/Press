<?php

namespace coderstape\Press\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use coderstape\Press\Series;

class SeriesFactory extends Factory
{
    protected $model = Series::class;

    public function definition(): array
    {
        $title = $this->faker->sentence(4);

        return [
            'title' => $title,
            'slug' => Str::slug($title),
        ];
    }
}

<?php

namespace coderstape\Press\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use coderstape\Press\Tag;

class TagFactory extends Factory
{
    protected $model = Tag::class;

    public function definition(): array
    {
        $name = $this->faker->sentence(4);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
        ];
    }
}

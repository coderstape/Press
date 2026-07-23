<?php

namespace coderstape\Press\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use coderstape\Press\Author;

class AuthorFactory extends Factory
{
    protected $model = Author::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name,
        ];
    }
}

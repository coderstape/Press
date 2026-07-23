<?php

namespace coderstape\Press\Database\Factories;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use coderstape\Press\Post;

class PostFactory extends Factory
{
    protected $model = Post::class;

    public function definition(): array
    {
        return [
            'identifier' => Str::random(),
            'slug' => Str::slug($this->faker->sentence),
            'title' => $this->faker->sentence,
            'body' => $this->faker->paragraph,
            'extra' => json_encode(['test' => 'value', 'author' => 'Test Author']),
            'published_at' => Carbon::now(),
        ];
    }
}

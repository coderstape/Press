<?php

namespace coderstape\Press\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use coderstape\Press\Post;
use coderstape\Press\Trending;

class TrendingFactory extends Factory
{
    protected $model = Trending::class;

    public function definition(): array
    {
        return [
            'post_id' => Post::factory(),
        ];
    }
}

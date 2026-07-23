<?php

namespace coderstape\Press\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use coderstape\Press\Blog;

class BlogFactory extends Factory
{
    protected $model = Blog::class;

    public function definition(): array
    {
        // Minimal valid Press markdown: head between --- markers, then
        // body. Shape mirrors tests/stubs/*.md (judgment value, veto ok).
        return [
            'data' => "---\nTitle: Factory Post---\nSome factory body",
        ];
    }
}

<?php

namespace vicgonvt\LaraPress;

use Illuminate\Support\ServiceProvider;

class LaraPressServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
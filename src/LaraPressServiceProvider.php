<?php

namespace vicgonvt\LaraPress;

use Illuminate\Support\ServiceProvider;
use vicgonvt\LaraPress\Console\ProcessCommand;

class LaraPressServiceProvider extends ServiceProvider
{
    protected $packageName = 'LaraPress';
    
    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        $this->commands([
            ProcessCommand::class,
        ]);
    }
}
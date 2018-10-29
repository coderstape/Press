<?php

namespace App\Providers;

use vicgonvt\LaraPress\LaraPressBaseServiceProvider;

class LaraPressServiceProvider extends LaraPressBaseServiceProvider
{
    /**
     * Bootstrap any package services.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        //
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        parent::register();

        //
    }

    /**
     * Bootstrap any additional custom field parsers.
     *
     * @return array
     */
    public function fields()
    {
        return [
            //
        ];
    }
}
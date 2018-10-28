<?php

namespace vicgonvt\LaraPress;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class LaraPressBaseServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any package services.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->registerPublishing();
        }

        $this->registerResources();
    }

    /**
     * Register the package resources such as routes, templates, etc.
     *
     * @return void
     */
    protected function registerResources()
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'larapress');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        $this->registerRoutes();
        $this->registerHelpers();
        $this->registerFacades();
    }

    /**
     * Register the package's publishable resources.
     *
     * @return void
     */
    protected function registerPublishing()
    {
        $this->publishes([
            __DIR__.'/LaraPressServiceProvider.php' => app_path('Providers/LaraPressServiceProvider.php'),
        ], 'larapress-provider');
        $this->publishes([
            __DIR__.'/../config/larapress.php' => config_path('larapress.php'),
        ], 'larapress-config');
    }

    /**
     * Register the package routes.
     *
     * @return void
     */
    protected function registerRoutes()
    {
        Route::group($this->routeConfiguration(), function () {
            $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        });
    }

    /**
     * Register the additional helpers needed.
     */
    protected function registerHelpers()
    {
        if (file_exists($file = __DIR__.'/Helpers/helpers.php')) {
            require $file;
        }
    }

    /**
     * Register any bindings to the app.
     */
    protected function registerFacades()
    {
        $this->app->singleton('LaraPress', function ($app) {
            return new LaraPress();
        });
    }

    /**
     * Get the LaraPress route group configuration array.
     *
     * @return array
     */
    protected function routeConfiguration()
    {
        return [
            'namespace' => 'vicgonvt\LaraPress\Http\Controllers',
            'prefix' => LaraPress::path(),
        ];
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->commands([
            Console\ProcessCommand::class,
        ]);
    }
}
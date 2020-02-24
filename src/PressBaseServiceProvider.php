<?php

namespace coderstape\Press;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use coderstape\Press\Facades\Press;

class PressBaseServiceProvider extends ServiceProvider
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
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'press');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        $this->registerHelpers();
        $this->registerFacades();
        $this->registerRoutes();
        $this->registerFields();
    }

    /**
     * Register the package's publishable resources.
     *
     * @return void
     */
    protected function registerPublishing()
    {
        $this->publishes([
            __DIR__ . '/Console/stubs/PressServiceProvider.stub' => app_path('Providers/PressServiceProvider.php'),
        ], 'press-provider');
        $this->publishes([
            __DIR__ . '/../config/press.php' => config_path('press.php'),
        ], 'press-config');
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
     * Bootstrap package fields.
     *
     * @return void
     */
    protected function registerFields()
    {
        press::fields([
            Field\Body::class,
            Field\Date::class,
            Field\Extra::class,
            Field\Identifier::class,
            Field\Permalink::class,
            Field\Series::class,
            Field\Tags::class,
            Field\Title::class,
        ]);
    }

    /**
     * Register the additional helpers needed.
     *
     * @return void
     */
    protected function registerHelpers()
    {
        if (file_exists($file = __DIR__.'/Helpers/helpers.php')) {
            require $file;
        }
    }

    /**
     * Register any bindings to the app.
     *
     * @return void
     */
    protected function registerFacades()
    {
        $this->app->singleton('Press', function ($app) {
            return new \coderstape\Press\Press();
        });
    }

    /**
     * Get the Press route group configuration array.
     *
     * @return array
     */
    protected function routeConfiguration()
    {
        return [
            'namespace' => 'coderstape\Press\Http\Controllers',
            'prefix' => Press::path(),
            'middleware' => 'web'
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

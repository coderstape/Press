<?php

namespace vicgonvt\LaraPress\Tests;

use vicgonvt\LaraPress\LaraPressServiceProvider;

abstract class TestCase extends \Orchestra\Testbench\TestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->withFactories(__DIR__.'/../database/factories');
    }

    /**
     * Bootstrap any service providers here.
     *
     * @param \Illuminate\Foundation\Application $app
     *
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [
            LaraPressServiceProvider::class,
        ];
    }

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        // Setup default database to use sqlite :memory:
        $app['config']->set('database.default', 'testdb');
        $app['config']->set('database.connections.testdb', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
        ]);
        $app['config']->set('larapress.prefix', '');
        $app['config']->set('larapress.driver', 'file');
    }
}
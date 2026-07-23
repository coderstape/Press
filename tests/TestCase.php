<?php

namespace coderstape\Press\Tests;

use coderstape\Press\PressBaseServiceProvider;

abstract class TestCase extends \Orchestra\Testbench\TestCase
{
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
            PressBaseServiceProvider::class,
        ];
    }

    /**
     * Bootstrap any aliases here.
     *
     * @param \Illuminate\Foundation\Application $app
     *
     * @return array
     */
    protected function getPackageAliases($app)
    {
        return [
            'Press' => 'coderstape\\Press\\Facades\\Press',
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
        // Testbench 11 no longer reliably populates app.key on the
        // TestCase path, and the one HTTP test here (TrendingTest's
        // visit recording) runs the web middleware group, whose
        // EncryptCookies throws MissingAppKeyException without it.
        // Set it explicitly rather than depending on Testbench's
        // defaulting internals -- Testbench's own encryption tests
        // do the same. 32 chars = a valid AES-256-CBC key.
        $app['config']->set('app.key', 'AckfSECXIvnK5r28GVIWUAxmbBSjTsmF');

        // Setup default database to use sqlite :memory:
        $app['config']->set('database.default', 'testdb');
        $app['config']->set('database.connections.testdb', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
        ]);
        $app['config']->set('press.prefix', '');
        $app['config']->set('press.driver', 'file');
    }
}
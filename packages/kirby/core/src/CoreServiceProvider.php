<?php

namespace Kirby\Core;

use Illuminate\Support\ServiceProvider;

/**
 * Class CoreServiceProvider.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class CoreServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'core');
        // publishing is only necessary when using the CLI
        if ($this->app->runningInConsole()) {
            $this->bootForConsole();
        }
    }

    /**
     * Register any package services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/core.php', 'core');

        // register the service the package provides
        $this->app->singleton('core', function ($app) {
            return new Core();
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['core'];
    }

    /**
     * Console-specific booting.
     *
     * @return void
     */
    protected function bootForConsole()
    {
        // publishing the configuration file
        $this->publishes([
            __DIR__.'/../config/core.php' => config_path('core.php'),
        ], 'core.config');
    }
}

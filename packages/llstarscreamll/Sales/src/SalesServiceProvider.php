<?php

namespace llstarscreamll\Sales;

use Illuminate\Support\ServiceProvider;

class SalesServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        // $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'llstarscreamll');
        // $this->loadViewsFrom(__DIR__.'/../resources/views', 'llstarscreamll');
        // $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        // $this->loadRoutesFrom(__DIR__.'/routes.php');

        // Publishing is only necessary when using the CLI.
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
        $this->mergeConfigFrom(__DIR__.'/../config/sales.php', 'sales');

        // Register the service the package provides.
        $this->app->singleton('sales', function ($app) {
            return new Sales;
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['sales'];
    }
    
    /**
     * Console-specific booting.
     *
     * @return void
     */
    protected function bootForConsole()
    {
        // Publishing the configuration file.
        $this->publishes([
            __DIR__.'/../config/sales.php' => config_path('sales.php'),
        ], 'sales.config');

        // Publishing the views.
        /*$this->publishes([
            __DIR__.'/../resources/views' => base_path('resources/views/vendor/llstarscreamll'),
        ], 'sales.views');*/

        // Publishing assets.
        /*$this->publishes([
            __DIR__.'/../resources/assets' => public_path('vendor/llstarscreamll'),
        ], 'sales.views');*/

        // Publishing the translation files.
        /*$this->publishes([
            __DIR__.'/../resources/lang' => resource_path('lang/vendor/llstarscreamll'),
        ], 'sales.views');*/

        // Registering package commands.
        // $this->commands([]);
    }
}

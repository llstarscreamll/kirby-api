<?php

namespace kirby\Products;

use Illuminate\Database\Eloquent\Factory as EloquentFactory;
use Illuminate\Support\ServiceProvider;

class ProductsServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     */
    public function boot(): void
    {
        // $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'kirby');
        // $this->loadViewsFrom(__DIR__.'/../resources/views', 'kirby');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadRoutesFrom(__DIR__.'/UI/API/V1/routes.php');

        // Publishing is only necessary when using the CLI.
        if ($this->app->runningInConsole()) {
            $this->bootForConsole();
        }

        if ($this->app->environment('local') || $this->app->runningUnitTests()) {
            $this->app->make(EloquentFactory::class)->load(__DIR__.'/../database/factories');
        }
    }

    /**
     * Register any package services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/products.php', 'products');

        // Register the service the package provides.
        $this->app->singleton('products', function ($app) {
            return new Products();
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['products'];
    }

    /**
     * Console-specific booting.
     */
    protected function bootForConsole(): void
    {
        // Publishing the configuration file.
        $this->publishes([
            __DIR__.'/../config/products.php' => config_path('products.php'),
        ], 'products.config');

        // Publishing the views.
        /*$this->publishes([
        __DIR__.'/../resources/views' => base_path('resources/views/vendor/kirby'),
        ], 'products.views');*/

        // Publishing assets.
        /*$this->publishes([
        __DIR__.'/../resources/assets' => public_path('vendor/kirby'),
        ], 'products.views');*/

        // Publishing the translation files.
        /*$this->publishes([
        __DIR__.'/../resources/lang' => resource_path('lang/vendor/kirby'),
        ], 'products.views');*/

        // Registering package commands.
        // $this->commands([]);
    }
}

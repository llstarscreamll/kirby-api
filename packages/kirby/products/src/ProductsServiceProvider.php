<?php

namespace kirby\Products;

use Illuminate\Support\ServiceProvider;
use Kirby\Products\Contracts\CategoryRepository;
use Kirby\Products\Repositories\EloquentCategoryRepository;

/**
 * Class ProductsServiceProvider.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class ProductsServiceProvider extends ServiceProvider
{
    /**
     * @var array
     */
    private $binds = [
        CategoryRepository::class => EloquentCategoryRepository::class,
    ];

    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        // $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'kirby');
        // $this->loadViewsFrom(__DIR__.'/../resources/views', 'kirby');
        $this->loadRoutesFrom(__DIR__.'/UI/API/V1/routes.php');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

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
        $this->mergeConfigFrom(__DIR__.'/../config/products.php', 'products');

        array_walk($this->binds, fn($concrete, $abstract) => $this->app->bind($abstract, $concrete));

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
     *
     * @return void
     */
    protected function bootForConsole()
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

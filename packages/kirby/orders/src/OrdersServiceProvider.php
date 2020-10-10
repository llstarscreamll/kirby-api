<?php

namespace Kirby\Orders;

use Illuminate\Support\ServiceProvider;
use Kirby\Orders\Contracts\OrderRepository;
use Kirby\Orders\Contracts\OrderProductRepository;
use Kirby\Orders\Repositories\EloquentOrderRepository;
use Kirby\Orders\Repositories\EloquentOrderProductRepository;

/**
 * Class OrdersServiceProvider.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class OrdersServiceProvider extends ServiceProvider
{
    /**
     * @var array
     */
    private $binds = [
        OrderRepository::class => EloquentOrderRepository::class,
        OrderProductRepository::class => EloquentOrderProductRepository::class,
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
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadRoutesFrom(__DIR__.'/UI/API/V1/routes.php');

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
        $this->mergeConfigFrom(__DIR__.'/../config/orders.php', 'orders');

        array_walk($this->binds, fn($concrete, $abstract) => $this->app->bind($abstract, $concrete));

        // Register the service the package provides.
        $this->app->singleton('orders', function ($app) {
            return new Orders();
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['orders'];
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
            __DIR__.'/../config/orders.php' => config_path('orders.php'),
        ], 'orders.config');

        // Publishing the views.
        /*$this->publishes([
        __DIR__.'/../resources/views' => base_path('resources/views/vendor/kirby'),
        ], 'orders.views');*/

        // Publishing assets.
        /*$this->publishes([
        __DIR__.'/../resources/assets' => public_path('vendor/kirby'),
        ], 'orders.views');*/

        // Publishing the translation files.
        /*$this->publishes([
        __DIR__.'/../resources/lang' => resource_path('lang/vendor/kirby'),
        ], 'orders.views');*/

        // Registering package commands.
        // $this->commands([]);
    }
}

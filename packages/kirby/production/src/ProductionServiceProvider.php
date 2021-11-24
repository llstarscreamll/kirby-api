<?php

namespace kirby\Production;

use Illuminate\Database\Eloquent\Factory as EloquentFactory;
use Illuminate\Support\ServiceProvider;
use Kirby\Production\Contracts\ProductionLogRepository;
use Kirby\Production\Repositories\EloquentProductionLogRepository;

class ProductionServiceProvider extends ServiceProvider
{
    /**
     * @var array
     */
    private $binds = [
        ProductionLogRepository::class => EloquentProductionLogRepository::class,
    ];

    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot(): void
    {
        $this->loadJsonTranslationsFrom(__DIR__.'/../resources/lang', 'production');
        // $this->loadViewsFrom(__DIR__.'/../resources/views', 'kirby');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadRoutesFrom(__DIR__.'/UI/API/V1/routes.php');

        // Publishing is only necessary when using the CLI.
        if ($this->app->runningInConsole()) {
            $this->bootForConsole();
        }

        if ($this->app->runningUnitTests()) {
            $this->app->make(EloquentFactory::class)->load(__DIR__.'/../database/factories');
        }
    }

    /**
     * Register any package services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/production.php', 'production');
        array_walk($this->binds, fn ($concrete, $abstract) => $this->app->bind($abstract, $concrete));

        // Register the service the package provides.
        $this->app->singleton('production', function ($app) {
            return new Production();
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['production'];
    }

    /**
     * Console-specific booting.
     *
     * @return void
     */
    protected function bootForConsole(): void
    {
        // Publishing the configuration file.
        $this->publishes([
            __DIR__.'/../config/production.php' => config_path('production.php'),
        ], 'production');

        // Publishing the views.
        /*$this->publishes([
        __DIR__.'/../resources/views' => base_path('resources/views/vendor/kirby'),
        ], 'production.views');*/

        // Publishing assets.
        /*$this->publishes([
        __DIR__.'/../resources/assets' => public_path('vendor/kirby'),
        ], 'production.views');*/

        // Publishing the translation files.
        /*$this->publishes([
        __DIR__.'/../resources/lang' => resource_path('lang/vendor/kirby'),
        ], 'production.views');*/

        // Registering package commands.
        // $this->commands([]);
    }
}

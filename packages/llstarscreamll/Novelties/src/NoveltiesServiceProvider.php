<?php

namespace llstarscreamll\Novelties;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Factory as EloquentFactory;
use llstarscreamll\Novelties\Providers\EventServiceProvider;
use llstarscreamll\Novelties\Contracts\NoveltyRepositoryInterface;
use llstarscreamll\Novelties\Repositories\EloquentNoveltyRepository;
use llstarscreamll\Novelties\Contracts\NoveltyTypeRepositoryInterface;
use llstarscreamll\Novelties\Repositories\EloquentNoveltyTypeRepository;

/**
 * Class NoveltiesServiceProvider.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class NoveltiesServiceProvider extends ServiceProvider
{
    /**
     * @var array
     */
    public $serviceProviders = [
        EventServiceProvider::class,
    ];

    /**
     * All of the container bindings that should be registered.
     *
     * @var array
     */
    public $bindings = [
        NoveltyRepositoryInterface::class => EloquentNoveltyRepository::class,
        NoveltyTypeRepositoryInterface::class => EloquentNoveltyTypeRepository::class,
    ];

    /**
     * All of the container singletons that should be registered.
     *
     * @var array
     */
    public $singletons = [
        'novelties' => Novelties::class,
    ];

    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        // $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'llstarscreamll');
        // $this->loadViewsFrom(__DIR__.'/../resources/views', 'llstarscreamll');
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        $this->loadRoutesFrom(__DIR__ . '/UI/API/V1/routes.php');
        $this->loadServiceProviders();

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
        $this->mergeConfigFrom(__DIR__ . '/../config/novelties.php', 'novelties');
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['novelties'];
    }

    private function loadServiceProviders()
    {
        foreach ($this->serviceProviders as $provider) {
            $this->app->register($provider);
        }
    }

    /**
     * Console-specific booting.
     *
     * @return void
     */
    protected function bootForConsole()
    {
        $this->app->make(EloquentFactory::class)->load(__DIR__ . '/../database/factories');

        // Publishing the configuration file.
        $this->publishes([
            __DIR__ . '/../config/novelties.php' => config_path('novelties.php'),
        ], 'novelties.config');

        // Publishing the views.
        /*$this->publishes([
        __DIR__.'/../resources/views' => base_path('resources/views/vendor/llstarscreamll'),
        ], 'novelties.views');*/

        // Publishing assets.
        /*$this->publishes([
        __DIR__.'/../resources/assets' => public_path('vendor/llstarscreamll'),
        ], 'novelties.views');*/

        // Publishing the translation files.
        /*$this->publishes([
        __DIR__.'/../resources/lang' => resource_path('lang/vendor/llstarscreamll'),
        ], 'novelties.views');*/

        // Registering package commands.
        // $this->commands([]);
    }
}

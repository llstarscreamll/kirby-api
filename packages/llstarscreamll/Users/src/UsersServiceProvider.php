<?php

namespace llstarscreamll\Users;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Factory as EloquentFactory;
use llstarscreamll\Users\Contracts\IdentificationRepositoryInterface;
use llstarscreamll\Users\Data\Repositories\EloquentIdentificationRepository;

/**
 * Class UsersServiceProvider.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class UsersServiceProvider extends ServiceProvider
{
    /**
     * @var array
     */
    private $binds = [
        IdentificationRepositoryInterface::class => EloquentIdentificationRepository::class,
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
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadRoutesFrom(__DIR__.'/UI/API/Routes/v1.php');

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
        $this->mergeConfigFrom(__DIR__.'/../config/users.php', 'users');

        // Register the service the package provides.
        $this->app->singleton('users', function ($app) {
            return new Users();
        });

        foreach ($this->binds as $abstract => $concrete) {
            $this->app->bind($abstract, $concrete);
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['users'];
    }

    /**
     * Console-specific booting.
     *
     * @return void
     */
    protected function bootForConsole()
    {
        $this->app->make(EloquentFactory::class)->load(__DIR__.'/../database/factories');

        // Publishing the configuration file.
        $this->publishes([
            __DIR__.'/../config/users.php' => config_path('users.php'),
        ], 'users.config');

        // Publishing the views.
        /*$this->publishes([
        __DIR__.'/../resources/views' => base_path('resources/views/vendor/llstarscreamll'),
        ], 'users.views');*/

        // Publishing assets.
        /*$this->publishes([
        __DIR__.'/../resources/assets' => public_path('vendor/llstarscreamll'),
        ], 'users.views');*/

        // Publishing the translation files.
        /*$this->publishes([
        __DIR__.'/../resources/lang' => resource_path('lang/vendor/llstarscreamll'),
        ], 'users.views');*/

        // Registering package commands.
        // $this->commands([]);
    }
}

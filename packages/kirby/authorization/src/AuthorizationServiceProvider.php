<?php

namespace Kirby\Authorization;

use Illuminate\Support\ServiceProvider;
use Kirby\Authorization\UI\CLI\Commands\RefreshAdminPermissionsCommand;

/**
 * Class AuthorizationServiceProvider.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class AuthorizationServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     */
    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        // publishing is only necessary when using the CLI
        if ($this->app->runningInConsole()) {
            $this->bootForConsole();
        }
    }

    /**
     * Register any package services.
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/authorization.php', 'authorization');

        // register the service the package provides
        $this->app->singleton('authorization', function ($app) {
            return new Authorization();
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['authorization'];
    }

    /**
     * Console-specific booting.
     */
    protected function bootForConsole()
    {
        // publishing the configuration file
        $this->publishes([
            __DIR__.'/../config/authorization.php' => config_path('authorization.php'),
        ], 'authorization.config');

        // Registering package commands.
        $this->commands([
            RefreshAdminPermissionsCommand::class,
        ]);
    }
}

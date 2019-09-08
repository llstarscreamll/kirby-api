<?php

namespace llstarscreamll\Authorization;

use Illuminate\Support\ServiceProvider;
use llstarscreamll\Authorization\UI\CLI\Commands\RefreshAdminPermissionsCommand;

/**
 * Class AuthorizationServiceProvider.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class AuthorizationServiceProvider extends ServiceProvider
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
        // $this->loadRoutesFrom(__DIR__.'/routes.php');
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
        $this->mergeConfigFrom(__DIR__.'/../config/authorization.php', 'authorization');

        // Register the service the package provides.
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
     *
     * @return void
     */
    protected function bootForConsole()
    {
        // Publishing the configuration file.
        $this->publishes([
            __DIR__.'/../config/authorization.php' => config_path('authorization.php'),
        ], 'authorization.config');

        // Publishing the views.
        /*$this->publishes([
        __DIR__.'/../resources/views' => base_path('resources/views/vendor/llstarscreamll'),
        ], 'authorization.views');*/

        // Publishing assets.
        /*$this->publishes([
        __DIR__.'/../resources/assets' => public_path('vendor/llstarscreamll'),
        ], 'authorization.views');*/

        // Publishing the translation files.
        /*$this->publishes([
        __DIR__.'/../resources/lang' => resource_path('lang/vendor/llstarscreamll'),
        ], 'authorization.views');*/

        // Registering package commands.
        $this->commands([
            RefreshAdminPermissionsCommand::class,
        ]);
    }
}

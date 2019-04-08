<?php
namespace llstarscreamll\TimeClock;

use Illuminate\Support\ServiceProvider;

class TimeClockServiceProvider extends ServiceProvider
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
        $this->loadMigrationsFrom(__DIR__.'/Data/Migrations');
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
        $this->mergeConfigFrom(__DIR__.'/../config/time-clock.php', 'time-clock');

        // Register the service the package provides.
        $this->app->singleton('TimeClock', function ($app) {
            return new TimeClock();
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['TimeClock'];
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
            __DIR__.'/../config/time-clock.php' => config_path('time-clock.php'),
        ], 'time-clock.config');

        // Publishing the views.
        /*$this->publishes([
        __DIR__.'/../resources/views' => base_path('resources/views/vendor/llstarscreamll'),
        ], 'time-clock.views');*/

        // Publishing assets.
        /*$this->publishes([
        __DIR__.'/../resources/assets' => public_path('vendor/llstarscreamll'),
        ], 'time-clock.views');*/

        // Publishing the translation files.
        /*$this->publishes([
        __DIR__.'/../resources/lang' => resource_path('lang/vendor/llstarscreamll'),
        ], 'time-clock.views');*/

        // Registering package commands.
        // $this->commands([]);
    }
}

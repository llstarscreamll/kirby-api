<?php

namespace Kirby\WorkShifts;

use Illuminate\Database\Eloquent\Factory as EloquentFactory;
use Illuminate\Support\ServiceProvider;
use Kirby\WorkShifts\Contracts\WorkShiftRepositoryInterface;
use Kirby\WorkShifts\Repositories\EloquentWorkShiftRepository;

/**
 * Class WorkShiftsServiceProvider.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class WorkShiftsServiceProvider extends ServiceProvider
{
    /**
     * @var array
     */
    private $binds = [
        WorkShiftRepositoryInterface::class => EloquentWorkShiftRepository::class,
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
        $this->mergeConfigFrom(__DIR__.'/../config/work-shifts.php', 'work-shifts');

        // Register the service the package provides.
        $this->app->singleton('workShifts', function ($app) {
            return new WorkShifts();
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
        return ['workShifts'];
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
            __DIR__.'/Config/work-shifts.php' => config_path('work-shifts.php'),
        ], 'work-shifts.config');

        // Publishing the views.
        /*$this->publishes([
        __DIR__.'/../resources/views' => base_path('resources/views/vendor/llstarscreamll'),
        ], 'work-shifts.views');*/

        // Publishing assets.
        /*$this->publishes([
        __DIR__.'/../resources/assets' => public_path('vendor/llstarscreamll'),
        ], 'work-shifts.views');*/

        // Publishing the translation files.
        /*$this->publishes([
        __DIR__.'/../resources/lang' => resource_path('lang/vendor/llstarscreamll'),
        ], 'work-shifts.views');*/

        // Registering package commands.
        // $this->commands([]);
    }
}
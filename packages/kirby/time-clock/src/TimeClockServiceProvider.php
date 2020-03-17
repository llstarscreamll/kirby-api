<?php

namespace Kirby\TimeClock;

use Illuminate\Database\Eloquent\Factory as EloquentFactory;
use Illuminate\Support\ServiceProvider;
use Kirby\TimeClock\Contracts\SettingRepositoryInterface;
use Kirby\TimeClock\Contracts\TimeClockLogRepositoryInterface;
use Kirby\TimeClock\Repositories\EloquentSettingRepository;
use Kirby\TimeClock\Repositories\EloquentTimeClockLogRepository;
use Kirby\TimeClock\UI\CLI\GenerateFakeTimeClockDataCommand;

/**
 * Class TimeClockServiceProvider.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class TimeClockServiceProvider extends ServiceProvider
{
    /**
     * @var array
     */
    private $binds = [
        SettingRepositoryInterface::class => EloquentSettingRepository::class,
        TimeClockLogRepositoryInterface::class => EloquentTimeClockLogRepository::class,
    ];

    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'time-clock');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadRoutesFrom(__DIR__.'/UI/API/V1/routes.php');

        // publishing is only necessary when using the CLI
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

        // register the service the package provides
        $this->app->singleton('TimeClock', function ($app) {
            return new TimeClock();
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
        return ['TimeClock'];
    }

    /**
     * Console-specific booting.
     *
     * @return void
     */
    protected function bootForConsole()
    {
        $this->app->make(EloquentFactory::class)->load(__DIR__.'/../database/factories');

        // publishing the configuration file
        $this->publishes([
            __DIR__.'/../config/time-clock.php' => config_path('time-clock.php'),
        ], 'time-clock.config');
    }
}

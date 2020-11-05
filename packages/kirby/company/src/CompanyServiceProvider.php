<?php

namespace Kirby\Company;

use Illuminate\Support\ServiceProvider;
use Kirby\Company\Contracts\CostCenterRepositoryInterface;
use Kirby\Company\Contracts\HolidayRepositoryInterface;
use Kirby\Company\Contracts\HolidaysServiceInterface;
use Kirby\Company\Contracts\SubCostCenterRepositoryInterface;
use Kirby\Company\Repositories\EloquentCostCenterRepository;
use Kirby\Company\Repositories\EloquentHolidayRepository;
use Kirby\Company\Repositories\EloquentSubCostCenterRepository;
use Kirby\Company\Services\HolidaysService;
use Kirby\Company\UI\CLI\SyncHolidaysCommand;

/**
 * Class CompanyServiceProvider.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class CompanyServiceProvider extends ServiceProvider
{
    /**
     * @var array
     */
    private $binds = [
        HolidaysServiceInterface::class => HolidaysService::class,
        HolidayRepositoryInterface::class => EloquentHolidayRepository::class,
        CostCenterRepositoryInterface::class => EloquentCostCenterRepository::class,
        SubCostCenterRepositoryInterface::class => EloquentSubCostCenterRepository::class,
    ];

    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
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
        $this->mergeConfigFrom(__DIR__.'/../config/company.php', 'company');

        // register the service the package provides
        $this->app->singleton('company', function ($app) {
            return new Company();
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
        return ['company'];
    }

    /**
     * Console-specific booting.
     *
     * @return void
     */
    protected function bootForConsole()
    {
        // publishing the configuration file
        $this->publishes([
            __DIR__.'/../config/company.php' => config_path('company.php'),
        ], 'company.config');

        // registering package commands.
        $this->commands([
            SyncHolidaysCommand::class,
        ]);
    }
}

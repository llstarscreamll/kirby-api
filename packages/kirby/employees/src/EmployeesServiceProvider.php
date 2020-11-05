<?php

namespace Kirby\Employees;

use Illuminate\Support\ServiceProvider;
use Kirby\Employees\Contracts\EmployeeRepositoryInterface;
use Kirby\Employees\Contracts\IdentificationRepositoryInterface;
use Kirby\Employees\Repositories\EloquentEmployeeRepository;
use Kirby\Employees\Repositories\EloquentIdentificationRepository;

/**
 * Class EmployeesServiceProvider.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class EmployeesServiceProvider extends ServiceProvider
{
    /**
     * @var array
     */
    private $binds = [
        EmployeeRepositoryInterface::class => EloquentEmployeeRepository::class,
        IdentificationRepositoryInterface::class => EloquentIdentificationRepository::class,
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
        $this->mergeConfigFrom(__DIR__.'/../config/employees.php', 'employees');

        // register the service the package provides
        $this->app->singleton('employees', function ($app) {
            return new Employees();
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
        return ['employees'];
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
            __DIR__.'/../config/employees.php' => config_path('employees.php'),
        ], 'employees.config');
    }
}

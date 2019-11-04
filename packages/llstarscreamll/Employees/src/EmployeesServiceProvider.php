<?php

namespace llstarscreamll\Employees;

use Illuminate\Database\Eloquent\Factory as EloquentFactory;
use Illuminate\Support\ServiceProvider;
use llstarscreamll\Employees\Contracts\EmployeeRepositoryInterface;
use llstarscreamll\Employees\Contracts\IdentificationRepositoryInterface;
use llstarscreamll\Employees\Data\Repositories\EloquentEmployeeRepository;
use llstarscreamll\Employees\Data\Repositories\EloquentIdentificationRepository;

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
        $this->mergeConfigFrom(__DIR__.'/../config/employees.php', 'employees');

        // Register the service the package provides.
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
        $this->app->make(EloquentFactory::class)->load(__DIR__.'/../database/factories');

        // Publishing the configuration file.
        $this->publishes([
            __DIR__.'/../config/employees.php' => config_path('employees.php'),
        ], 'employees.config');

        // Publishing the views.
        /*$this->publishes([
        __DIR__.'/../resources/views' => base_path('resources/views/vendor/llstarscreamll'),
        ], 'employees.views');*/

        // Publishing assets.
        /*$this->publishes([
        __DIR__.'/../resources/assets' => public_path('vendor/llstarscreamll'),
        ], 'employees.views');*/

        // Publishing the translation files.
        /*$this->publishes([
        __DIR__.'/../resources/lang' => resource_path('lang/vendor/llstarscreamll'),
        ], 'employees.views');*/

        // Registering package commands.
        // $this->commands([]);
    }
}

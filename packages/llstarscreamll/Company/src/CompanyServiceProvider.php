<?php
namespace llstarscreamll\Company;

use Illuminate\Support\ServiceProvider;
use llstarscreamll\Company\Services\HolidaysService;
use llstarscreamll\Company\UI\CLI\SyncHolidaysCommand;
use Illuminate\Database\Eloquent\Factory as EloquentFactory;
use llstarscreamll\Company\Contracts\HolidaysServiceInterface;
use llstarscreamll\Company\Contracts\HolidayRepositoryInterface;
use llstarscreamll\Company\Contracts\CostCenterRepositoryInterface;
use llstarscreamll\Company\Data\Repositories\EloquentHolidayRepository;
use llstarscreamll\Company\Data\Repositories\EloquentCostCenterRepository;

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
        $this->mergeConfigFrom(__DIR__.'/../config/company.php', 'company');

        // Register the service the package provides.
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
        $this->app->make(EloquentFactory::class)->load(__DIR__.'/../database/factories');

        // Publishing the configuration file.
        $this->publishes([
            __DIR__.'/../config/company.php' => config_path('company.php'),
        ], 'company.config');

        // Publishing the views.
        /*$this->publishes([
        __DIR__.'/../resources/views' => base_path('resources/views/vendor/llstarscreamll'),
        ], 'company.views');*/

        // Publishing assets.
        /*$this->publishes([
        __DIR__.'/../resources/assets' => public_path('vendor/llstarscreamll'),
        ], 'company.views');*/

        // Publishing the translation files.
        /*$this->publishes([
        __DIR__.'/../resources/lang' => resource_path('lang/vendor/llstarscreamll'),
        ], 'company.views');*/

        // registering package commands.
        $this->commands([
            SyncHolidaysCommand::class,
        ]);
    }
}

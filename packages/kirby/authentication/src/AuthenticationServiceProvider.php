<?php

namespace Kirby\Authentication;

use Carbon\Carbon;
use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;

/**
 * Class AuthenticationServiceProvider.
 * 
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class AuthenticationServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        Passport::routes();
        Passport::tokensExpireIn(Carbon::now()->addMinutes(config('authentication.api.token-expires-in')));
        Passport::refreshTokensExpireIn(Carbon::now()->addMinutes(config('authentication.api.refresh-token-expires-in')));

        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');

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
        $this->mergeConfigFrom(__DIR__.'/../config/authentication.php', 'authentication');

        // register the service the package provides
        $this->app->singleton('authentication', function ($app) {
            return new Authentication();
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['authentication'];
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
            __DIR__.'/../config/authentication.php' => config_path('authentication.php'),
        ], 'authentication.config');
    }
}

<?php

namespace Kirby\Users;

use Illuminate\Database\Eloquent\Factory as EloquentFactory;
use Illuminate\Support\ServiceProvider;
use Kirby\Users\Contracts\UserRepositoryInterface;
use Kirby\Users\Repositories\EloquentUserRepository;

/**
 * Class UsersServiceProvider.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class UsersServiceProvider extends ServiceProvider
{
    /**
     * @var array
     */
    private $binds = [
        UserRepositoryInterface::class => EloquentUserRepository::class,
    ];

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
        $this->mergeConfigFrom(__DIR__.'/../config/users.php', 'users');

        // register the service the package provides
        $this->app->singleton('users', fn () => new Users());

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
        return ['users'];
    }

    /**
     * Console-specific booting.
     */
    protected function bootForConsole()
    {
        $this->app->make(EloquentFactory::class)->load(__DIR__.'/../database/factories');

        // publishing the configuration file
        $this->publishes([
            __DIR__.'/../config/users.php' => config_path('users.php'),
        ], 'users.config');
    }
}

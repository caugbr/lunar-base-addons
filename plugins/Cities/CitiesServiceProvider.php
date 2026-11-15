<?php

namespace Plugins\Cities;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Plugins\Cities\Console\SeedCitiesCommand;

class CitiesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $routesFile = __DIR__ . '/routes.php';
        if (file_exists($routesFile)) {
            $this->loadRoutesFrom($routesFile);
        }
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'cities');

        if ($this->app->runningInConsole()) {
            $this->commands([
                SeedCitiesCommand::class,
            ]);
        }

        Blade::component('cities::components.autocomplete', 'cities-autocomplete');
    }
}

<?php

namespace Plugins\Swetest;

use Illuminate\Support\ServiceProvider;
use Plugins\Swetest\Console\CheckSwetestCommand;

class SwetestServiceProvider extends ServiceProvider
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
        if ($this->app->runningInConsole()) {
            $this->commands([
                CheckSwetestCommand::class,
            ]);
        }
    }
}

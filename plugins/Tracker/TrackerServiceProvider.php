<?php

namespace Plugins\Tracker;

use Illuminate\Support\ServiceProvider;
use App\Support\AdminMenu;
use Plugins\Tracker\Http\Middleware\TrackPageViews;

class TrackerServiceProvider extends ServiceProvider
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
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'tracker');

        $menuAfterItem = config('pluginSettings.Tracker.menuAfterItem', 'Temas');
        $menuSet = config('pluginSettings.Tracker.menuSet', 1);

        AdminMenu::add([
            'label' => 'Tráfego',
            'icon' => 'activity',
            'route' => 'admin.tracker.index',
            'active' => 'admin.tracker.*',
            'permission' => 'manage-pages',
        ], $menuAfterItem, $menuSet);

        $this->app['router']->pushMiddlewareToGroup('web', TrackPageViews::class);
    }
}

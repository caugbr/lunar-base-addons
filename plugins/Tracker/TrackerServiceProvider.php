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

        AdminMenu::addSubItem('Tráfego', [
            'label'      => 'Mais Visitadas',
            'icon'       => 'file-text',
            'route'      => 'admin.tracker.pages',
            'active'     => 'admin.tracker.pages',
            'role'       => 'admin',
        ]);

        AdminMenu::addSubItem('Tráfego', [
            'label'      => 'Por Horário',
            'icon'       => 'clock',
            'route'      => 'admin.tracker.hourly',
            'active'     => 'admin.tracker.hourly',
            'role'       => 'admin',
        ]);

        AdminMenu::addSubItem('Tráfego', [
            'label'      => 'Origens de Tráfego',
            'icon'       => 'globe',
            'route'      => 'admin.tracker.referrers',
            'active'     => 'admin.tracker.referrers',
            'role'       => 'admin',
        ]);

        AdminMenu::addSubItem('Tráfego', [
            'label'      => 'Eventos',
            'icon'       => 'mouse-pointer-click',
            'route'      => 'admin.tracker.events',
            'active'     => 'admin.tracker.events',
            'role'       => 'admin',
        ]);

        $this->app['router']->pushMiddlewareToGroup('web', TrackPageViews::class);

        if (!request()->is('admin*') && !request()->ajax()) {
            $this->registerEventTrackerScript();
        }
    }

    private function registerEventTrackerScript(): void
    {
        // $eventUrl  = route('tracker.api.event');
        $eventUrl  = url('/tracker/api/event');
        $csrfToken = csrf_token();

        if (function_exists('add_inline_script')) {
            $inlineConfig = "window.TRACKER_CONFIG = { eventUrl: '{$eventUrl}', csrfToken: '{$csrfToken}' };";
            add_inline_script($inlineConfig);
        }

        if (function_exists('add_script')) {
            // Ajuste o caminho do asset conforme a estrutura pública de plugins do seu sistema
            add_script(
                'tracker-events',
                asset('plugins/tracker/js/eventTracker.js'),
                [],
                '1.0.0',
                true, // inFooter
                true  // defer
            );
        }
    }
}

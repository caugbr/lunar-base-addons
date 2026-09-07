<?php

namespace Plugins\Redirects;

use Illuminate\Support\ServiceProvider;
use App\Support\AdminMenu;
use App\Support\HookManager;
use App\Support\Settings;
use Plugins\Redirects\Http\Middleware\HandleRedirects;
use Plugins\Redirects\Http\Middleware\CaptureNotFound;

class RedirectsServiceProvider extends ServiceProvider
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
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'redirects');

        AdminMenu::addSubItem('Ferramentas', [
            'label'      => 'Redirecionamentos',
            'icon'       => 'repeat',
            'route'      => 'admin.redirects.index',
            'active'     => 'admin.redirects.index',
            'role'       => 'admin',
        ]);

        HookManager::register('admin.tools_page', function($params) {
            if (view()->exists('admin.tools.tool-card')) {
                return view('admin.tools.tool-card', [
                    'icon' => 'repeat',
                    'title' => 'Redirecionamentos',
                    'text' => 'Crie redirecionamentos 301 ou 302 para URLs do site',
                    'buttonTarget' => route('admin.redirects.index'),
                    'buttonLabel' => 'Gerenciar redirects',
                ])->render();
            }
            return '';
        }, 'Redirects Plugin');

        AdminMenu::addSubItem('Referências', [
            'label'      => 'Erros 404',
            'icon'       => 'alert-triangle',
            'route'      => 'admin.redirects.404',
            'active'     => 'admin.redirects.404',
            'role'       => 'admin',
        ]);

        // Registra Middlewares globais na pilha web
        $router = $this->app['router'];
        $router->pushMiddlewareToGroup('web', HandleRedirects::class);

        if (config('pluginSettings.Redirects.log404', true)) {
            $router->pushMiddlewareToGroup('web', CaptureNotFound::class);
        }

        // Configurações em Admin -> Configurações
        $this->registerSettings();
    }

    protected function registerSettings(): void
    {
        Settings::add([
            'type'  => 'subtitle',
            'icon'  => 'repeat',
            'label' => 'Configurações de Redirecionamentos',
        ], 'general');

        Settings::add([
            'key'         => 'redirects_log_404',
            'type'        => 'switch',
            'label'       => 'Capturar Erros 404',
            'description' => 'Armazena tentativas de acesso a URLs inexistentes para análise e criação de redirecionamentos.',
            'active'      => 'Capturar',
            'inactive'    => 'Não capturar',
            'default'     => true,
        ], 'general');
    }
}

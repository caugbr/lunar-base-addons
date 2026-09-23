<?php

namespace Plugins\UserSwitcher;

use Illuminate\Support\ServiceProvider;
use App\Support\HookManager;

class UserSwitcherServiceProvider extends ServiceProvider
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
        // Carrega as views com namespace 'user-switcher'
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'user-switcher');

        // Hook: Injeta o botão "Assumir" nas ações da listagem de usuários
        HookManager::register('admin.user_list_actions', function ($params = []) {
            $user = $params['user'] ?? null;
            if (!$user) {
                return '';
            }

            // Somente admins podem ver o botão
            $currentLogged = auth()->user();
            if (!$currentLogged || ($currentLogged->role !== 'admin' && !$currentLogged->can('manage-users'))) {
                return '';
            }

            return view('user-switcher::action-button', compact('user'))->render();
        }, 'UserSwitcher List Action');

        // Hook: Injeta a barra de alerta no cabeçalho quando estiver simulando
        HookManager::register('admin.header_user_start', function () {
            if (session()->has('impersonate_original_admin_id')) {
                return view('user-switcher::header-indicator')->render();
            }
            return '';
        }, 'UserSwitcher Header Alert');
    }
}

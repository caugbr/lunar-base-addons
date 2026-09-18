<?php

namespace Plugins\RoleSwitcher;

use Illuminate\Support\ServiceProvider;
use App\Support\HookManager;
use App\Support\AdminMenu;

class RoleSwitcherServiceProvider extends ServiceProvider
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
        // 1. Carrega Views do Plugin
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'role-switcher');

        // 2. Registra o Item de Menu na Sidebar
        $menuAfterItem = config('pluginSettings.RoleSwitcher.menuAfterItem', 'Configurações');
        $menuSet = (int) config('pluginSettings.RoleSwitcher.menuSet', 1);

        AdminMenu::add([
            'label'      => 'Alternar Papéis',
            'icon'       => 'arrow-left-right',
            'route'      => 'admin.role-switcher.matrix',
            'active'     => 'admin.role-switcher.*',
            'permission' => 'manage-settings',
        ], $menuAfterItem, $menuSet);

        // Injeta o middleware na pilha web para interceptar antes dos controllers e views
        $this->app['router']->pushMiddlewareToGroup(
            'web',
            \Plugins\RoleSwitcher\Http\Middleware\RoleSwitcherMiddleware::class
        );

        HookManager::register('admin.header_user_start', function ($params = []) {
            $currentSwitchedRole = session('role_switcher_active');

            if ($currentSwitchedRole) {
                $allRoles = config('rolesPermissions.roles', []);
                $currentRole = $allRoles[$currentSwitchedRole]['name'] ?? ucfirst($currentSwitchedRole);

                return view('role-switcher::current-role', compact('currentRole'))->render();
            }

            return '';
        }, 'RoleSwitcher Header');

        // 4. Injeta o Card de Alternância abaixo do Perfil do Usuário
        HookManager::register('admin.profile_after_card', function ($params = []) {
            $user = $params['user'] ?? auth()->user();
            if (!$user) {
                return '';
            }

            $realRole = $user->getOriginal('role') ?? $user->role;

            $matrix = getOption('role_switcher_matrix', []);
            if (is_string($matrix)) {
                $matrix = json_decode($matrix, true) ?? [];
            }

            $availableRoles = $matrix[$realRole] ?? [];

            // Se esse usuário não tiver permissão para assumir nenhum papel, não renderiza nada
            if (empty($availableRoles)) {
                return '';
            }

            $allRoles = config('rolesPermissions.roles', []);
            $currentSwitchedRole = session('role_switcher_active');

            return view('role-switcher::profile-switcher', compact(
                'availableRoles',
                'allRoles',
                'currentSwitchedRole',
                'realRole'
            ))->render();
        }, 'RoleSwitcher Plugin');
    }
}

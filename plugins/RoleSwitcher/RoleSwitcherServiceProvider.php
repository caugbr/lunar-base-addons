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
        // Carrega Views do Plugin
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'role-switcher');

        // Registra o Item de Menu na Sidebar
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

        // Injeta o Card de Alternância abaixo do Perfil do Usuário
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

        // TRAVA DE SEGURANÇA: Impede que o 'role' simulado seja gravado no banco de dados
        \App\Models\User::saving(function ($user) {
            if (session()->has('role_switcher_active') && session()->has('role_switcher_real_role')) {
                // Garante que, se o model User for salvo no banco, o 'role' gravado seja SEMPRE o original
                $user->role = session('role_switcher_real_role');
            }
        });

        \App\Models\User::saved(function ($user) {
            if (session()->has('role_switcher_active')) {
                // Após a gravação no banco, reaplica o papel simulado em memória para a requisição atual
                $switchedRole = session('role_switcher_active');
                $user->setAttribute('role', $switchedRole);
                $user->syncOriginalAttribute('role'); // Evita que continue 'dirty'
            }
        });
    }
}

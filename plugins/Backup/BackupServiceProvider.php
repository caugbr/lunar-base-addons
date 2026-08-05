<?php

namespace Plugins\Backup;

use Illuminate\Support\ServiceProvider;
use App\Support\AdminMenu;
use App\Support\HookManager;
use Plugins\Backup\Console\Commands\BackupRunCommand;

class BackupServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $routesFile = __DIR__ . '/routes.php';
        if (file_exists($routesFile)) {
            require $routesFile;
        }
    }

    public function boot(): void
    {
        // 1. Carrega as views do plugin
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'backup');

        // 2. Adiciona item de menu na sidebar do Admin
        AdminMenu::addSubItem('Ferramentas', [
            'label'      => 'Backups',
            'icon'       => 'archive',
            'route'      => 'admin.backups.index',
            'active'     => 'admin.backups.*',
            'role'       => 'admin',
        ], 'Exportar / Importar');

        HookManager::register('admin.tools_page', function($params) {
            if (view()->exists('admin.tools.tool-card')) {
                return view('admin.tools.tool-card', [
                    'icon' => 'archive',
                    'title' => 'Backups',
                    'text' => 'Faça e gerencie backups completos do seu conteúdo (database, temas e plugins)',
                    'buttonTarget' => route('admin.backups.index'),
                    'buttonLabel' => 'Backup',
                ])->render();
            }
            return '';
        }, 'Backup Plugin');

        // 3. Registra comando Artisan
        if ($this->app->runningInConsole()) {
            $this->commands([
                BackupRunCommand::class,
            ]);
        }
    }
}

<?php

namespace Plugins\Backup;

use Illuminate\Support\ServiceProvider;
use App\Support\AdminMenu;
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
        AdminMenu::add([
            'label'      => 'Backups',
            'icon'       => 'archive',
            'route'      => 'admin.backups.index',
            'active'     => 'admin.backups.*',
            'role'       => 'admin',
        ], 'Sistema');

        // 3. Registra comando Artisan
        if ($this->app->runningInConsole()) {
            $this->commands([
                BackupRunCommand::class,
            ]);
        }
    }
}

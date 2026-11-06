<?php

namespace Plugins\Outreach;

use Illuminate\Support\ServiceProvider;
use App\Support\AdminMenu;

class OutreachServiceProvider extends ServiceProvider
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
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'outreach');

        // Cria a seção "Prospecção" no menu lateral da administração
        AdminMenu::add([
            'label'  => 'Prospecção',
            'icon'   => 'send',
            'route'  => 'admin.outreach.clients.index',
            'active' => 'admin.outreach.*',
            'role'   => 'admin',
        ]);

        AdminMenu::addSubItem('Prospecção', [
            'label'  => 'Contatos',
            'icon'   => 'users',
            'route'  => 'admin.outreach.clients.index',
            'active' => 'admin.outreach.clients.index',
            'role'   => 'admin',
        ]);

        AdminMenu::addSubItem('Prospecção', [
            'label'  => 'Criar contato',
            'icon'   => 'user-plus',
            'route'  => 'admin.outreach.clients.create',
            'active' => 'admin.outreach.clients.create',
            'role'   => 'admin',
        ]);

        AdminMenu::addSubItem('Prospecção', [
            'label'  => 'Modelos de E-mail',
            'icon'   => 'files',
            'route'  => 'admin.outreach.templates.index',
            'active' => 'admin.outreach.templates.index',
            'role'   => 'admin',
        ]);

        AdminMenu::addSubItem('Prospecção', [
            'label'  => 'Nome modelo',
            'icon'   => 'file-plus-corner',
            'route'  => 'admin.outreach.templates.create',
            'active' => 'admin.outreach.templates.create',
            'role'   => 'admin',
        ]);
    }
}

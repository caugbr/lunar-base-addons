<?php

namespace Plugins\TelegramNotifier;

use Illuminate\Support\ServiceProvider;
use App\Support\AdminMenu;

class TelegramNotifierServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Carrega as rotas
        $routesFile = __DIR__ . '/routes.php';
        if (file_exists($routesFile)) {
            $this->loadRoutesFrom($routesFile);
        }
    }

    public function boot(): void
    {
        // Registra as views do plugin
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'telegram-notifier');

        // Adiciona o item ao menu administrativo
        $menuAfterItem = config('pluginSettings.TelegramNotifier.menuAfterItem', 'Configurações');
        $menuSet = config('pluginSettings.TelegramNotifier.menuSet', 1);

        AdminMenu::add([
            'label'  => 'Telegram Notifier',
            'icon'   => 'send',
            'route'  => 'admin.telegram-notifier.index',
            'active' => 'admin.telegram-notifier.*',
            'role'   => 'admin',
        ], $menuAfterItem, $menuSet);
    }
}

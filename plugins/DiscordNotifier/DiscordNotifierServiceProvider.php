<?php

namespace Plugins\DiscordNotifier;

use Illuminate\Support\ServiceProvider;
use App\Support\AdminMenu;

class DiscordNotifierServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Carrega o helper global da função discordNotify()
        $helperFile = __DIR__ . '/Helpers/discord_helpers.php';
        if (file_exists($helperFile)) {
            require_once $helperFile;
        }

        // Carrega as rotas
        $routesFile = __DIR__ . '/routes.php';
        if (file_exists($routesFile)) {
            require $routesFile;
        }
    }

    public function boot(): void
    {
        // Carrega as views do plugin
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'discord-notifier');

        // Adiciona item de menu na sidebar da Admin
        $menuAfterItem = config('pluginSettings.DiscordNotifier.menuAfterItem', 'Configurações');
        $menuSet = config('pluginSettings.DiscordNotifier.menuSet', 1);

        AdminMenu::add([
            'label'  => 'Discord Notifier',
            'icon'   => 'hash',
            'route'  => 'admin.discord-notifier.index',
            'active' => 'admin.discord-notifier.*',
            'role'   => 'admin',
        ], $menuAfterItem, $menuSet);
    }
}

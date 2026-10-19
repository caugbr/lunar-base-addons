<?php

namespace Plugins\WhatsAppButton;

use Illuminate\Support\ServiceProvider;
use App\Support\AdminMenu;
use App\Support\HookManager;
use Plugins\WhatsAppButton\Helpers\WhatsAppHelper;

class WhatsAppButtonServiceProvider extends ServiceProvider
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
        // Registra as views do plugin
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'whatsapp-button');

        // Adiciona ao menu administrativo
        $menuAfterItem = config('pluginSettings.WhatsAppButton.menuAfterItem', 'Configurações');
        $menuSet = config('pluginSettings.WhatsAppButton.menuSet', 1);

        AdminMenu::add([
            'label'  => 'WhatsApp',
            'icon'   => 'message-circle',
            'route'  => 'admin.whatsapp-button.index',
            'active' => 'admin.whatsapp-button.*',
            'role'   => 'admin',
        ], $menuAfterItem, $menuSet);

        // Injeta automaticamente no hook do rodapé do tema público
        HookManager::register('main.after_content', function($params) {
            if (!WhatsAppHelper::shouldDisplay()) {
                return '';
            }

            $buttonData = WhatsAppHelper::getButtonData();
            return view('whatsapp-button::frontend.button', $buttonData)->render();
        }, 'WhatsApp Button');
    }
}

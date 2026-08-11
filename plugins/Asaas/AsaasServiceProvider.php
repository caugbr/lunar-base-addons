<?php

namespace Plugins\Asaas;

use Illuminate\Support\ServiceProvider;
use App\Support\AdminMenu;
use App\Support\Settings;

class AsaasServiceProvider extends ServiceProvider
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
        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'asaas');

        $menuAfterItem = config('pluginSettings.Asaas.menuAfterItem', 'Usuários');
        $menuSet = config('pluginSettings.Asaas.menuSet', 1);

        // 1. Registra o menu de vendas na sidebar
        AdminMenu::add([
            'label'      => 'Vendas Asaas',
            'icon'       => 'credit-card',
            'route'      => 'admin.asaas.invoices.index',
            'active'     => 'admin.asaas.*',
            'role'       => 'admin',
        ], $menuAfterItem, $menuSet);

        // 2. Registra as configurações do Asaas em Admin -> Configurações
        $this->registerSettings();
    }

    protected function registerSettings(): void
    {
        Settings::add([
            'type'  => 'subtitle',
            'icon'  => 'credit-card',
            'label' => 'Configurações Asaas (Gateway)',
        ], 'general');

        Settings::add([
            'key'         => 'asaas_environment',
            'type'        => 'select',
            'label'       => 'Ambiente Asaas',
            'default'     => 'sandbox',
            'options'     => ['sandbox' => 'Sandbox (Testes)', 'production' => 'Produção'],
        ], 'general');

        Settings::add([
            'key'         => 'asaas_sandbox_api_key',
            'type'        => 'password',
            'label'       => 'API Key (Sandbox)',
            'default'     => '',
        ], 'general');

        Settings::add([
            'key'         => 'asaas_production_api_key',
            'type'        => 'password',
            'label'       => 'API Key (Produção)',
            'default'     => '',
        ], 'general');

        Settings::add([
            'key'         => 'asaas_webhook_token',
            'type'        => 'text',
            'label'       => 'Token do Webhook',
            'description' => 'Cole aqui o token de autenticação configurado no painel do Asaas',
            'default'     => '',
        ], 'general');
    }
}

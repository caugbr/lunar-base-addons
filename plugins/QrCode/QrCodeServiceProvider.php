<?php

namespace Plugins\QrCode;

use Illuminate\Support\ServiceProvider;
use App\Support\HookManager;
use App\Support\AdminMenu;

class QrCodeServiceProvider extends ServiceProvider
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
        // 1. Carrega as views com o namespace "qrcode"
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'qrcode');

        $menuAfterItem = config('pluginSettings.QrCode.menuAfterItem', 'Configurações');
        $menuSet = config('pluginSettings.QrCode.menuSet', 1);

        // 2. Injeta o item no menu lateral de Ferramentas / Conteúdo
        AdminMenu::add([
            'label'      => 'Gerador QR Code',
            'icon'       => 'qr-code',
            'route'      => 'admin.qrcode.index',
            'active'     => 'admin.qrcode.*',
            'permission' => 'manage-pages',
        ], $menuAfterItem, $menuSet);

        // 3. Registra os Hooks para Posts e Páginas
        $this->registerHooks();
    }

    protected function registerHooks(): void
    {
        // Action na tabela de Posts
        HookManager::register('admin.post_actions', function ($params) {
            $post = $params['post'] ?? null;
            if (!$post) return '';

            $url = $post->url ?? url("posts/{$post->slug}");

            return view('qrcode::hooks.action-btn', [
                'type'  => 'post',
                'item'  => $post,
                'url'   => $url,
                'title' => $post->title ?? 'Post',
            ])->render();
        }, 'QrCode Plugin');

        // Action na tabela de Páginas
        HookManager::register('admin.page_actions', function ($params) {
            $page = $params['page'] ?? null;
            if (!$page) return '';

            $url = $page->url ?? url($page->slug ?? '');

            return view('qrcode::hooks.action-btn', [
                'type'  => 'page',
                'item'  => $page,
                'url'   => $url,
                'title' => $page->title ?? 'Página',
            ])->render();
        }, 'QrCode Plugin');

        // Botão no Header de Edição de Post
        HookManager::register('admin.edit_post_header_buttons', function ($params) {
            $post = $params['post'] ?? null;
            if (!$post) return '';

            $url = $post->url ?? url("posts/{$post->slug}");

            return view('qrcode::hooks.header-btn', [
                'type'  => 'post',
                'item'  => $post,
                'url'   => $url,
                'title' => $post->title ?? 'Post',
            ])->render();
        }, 'QrCode Plugin');

        // Botão no Header de Edição de Página
        HookManager::register('admin.edit_page_header_buttons', function ($params) {
            $page = $params['page'] ?? null;
            if (!$page) return '';

            $url = $page->url ?? url($page->slug ?? '');

            return view('qrcode::hooks.header-btn', [
                'type'  => 'page',
                'item'  => $page,
                'url'   => $url,
                'title' => $page->title ?? 'Página',
            ])->render();
        }, 'QrCode Plugin');
    }
}

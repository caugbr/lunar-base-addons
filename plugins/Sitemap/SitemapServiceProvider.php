<?php

namespace Plugins\Sitemap;

use Illuminate\Support\ServiceProvider;
use App\Support\AdminMenu;
use App\Support\HookManager;
use App\Support\Settings;

class SitemapServiceProvider extends ServiceProvider
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
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'sitemap');

        // Registra o item no menu lateral (em Ferramentas)
        AdminMenu::addSubItem('Ferramentas', [
            'label'      => 'Sitemap',
            'icon'       => 'globe',
            'route'      => 'admin.sitemap.index',
            'active'     => 'admin.sitemap.index',
            'role'       => 'admin',
        ]);

        HookManager::register('admin.tools_page', function($params) {
            if (view()->exists('admin.tools.tool-card')) {
                return view('admin.tools.tool-card', [
                    'icon' => 'globe',
                    'title' => 'Sitemap',
                    'text' => 'Sitemap para buscadores. Copie a URL ou baixe o arquivo.',
                    'buttonTarget' => route('admin.sitemap.index'),
                    'buttonLabel' => 'Sitemap',
                ])->render();
            }
            return '';
        }, 'Sitemap Plugin');

        // Registra opções em Admin -> Configurações
        $this->registerSettings();
    }

    protected function registerSettings(): void
    {
        Settings::add([
            'type'  => 'subtitle',
            'icon'  => 'globe',
            'label' => 'Configurações de Sitemap XML',
        ], 'general');

        Settings::add([
            'key'         => 'sitemap_include_posts',
            'type'        => 'switch',
            'label'       => 'Incluir Posts',
            'active'      => 'Sim',
            'inactive'    => 'Não',
            'default'     => true,
        ], 'general');

        Settings::add([
            'key'         => 'sitemap_include_pages',
            'type'        => 'switch',
            'label'       => 'Incluir Páginas',
            'active'      => 'Sim',
            'inactive'    => 'Não',
            'default'     => true,
        ], 'general');

        Settings::add([
            'key'         => 'sitemap_include_taxonomies',
            'type'        => 'switch',
            'label'       => 'Incluir Taxonomias (Categorias/Tags)',
            'active'      => 'Sim',
            'inactive'    => 'Não',
            'default'     => true,
        ], 'general');
    }
}

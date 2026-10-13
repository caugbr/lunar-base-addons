<?php

namespace Plugins\VisualSnippets;

use Illuminate\Support\ServiceProvider;
use App\Helpers\ContentHelper;
use App\Support\AdminMenu;
use App\Support\HookManager;
use Plugins\VisualSnippets\Models\VisualSnippet;
use App\Services\EditorManager;

class VisualSnippetsServiceProvider extends ServiceProvider
{
    /**
     * Registro de serviços e carregamento de rotas.
     */
    public function register(): void
    {
        $routesFile = __DIR__ . '/routes.php';
        if (file_exists($routesFile)) {
            $this->loadRoutesFrom($routesFile);
        }
    }

    /**
     * Inicialização dos componentes no boot do Lunar Base.
     */
    public function boot(): void
    {
        // Migrations e Views com Namespace exclusivo
        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'visual-snippets');

        // Registra os Shortcodes no Core (se habilitado nas configurações)
        if (setting('pluginSettings.VisualSnippets.enableShortcodes', true)) {
            $this->registerShortcodes();
        }

        // Injeta o item e subitens no Menu Administrativo do Painel
        $menuAfterItem = setting('pluginSettings.VisualSnippets.menuAfterItem', 'Formulários');
        $menuSet = (int) setting('pluginSettings.VisualSnippets.menuSet', 1);

        AdminMenu::add([
            'label'      => 'Snippets Visuais',
            'icon'       => 'layout-template', // Ícone Lucide
            'route'      => 'admin.visual-snippets.index',
            'active'     => 'admin.visual-snippets.*',
            'permission' => 'manage-pages',
        ], $menuAfterItem, $menuSet);

        AdminMenu::addSubItem('Snippets Visuais', [
            'label'      => 'Novo Snippet',
            'icon'       => 'plus',
            'route'      => 'admin.visual-snippets.create',
            'active'     => 'admin.visual-snippets.create',
            'permission' => 'manage-pages',
        ]);

        HookManager::register('admin.editor_scripts', function() {
            $snippets = visual_snippets_payload_map();
            echo "<script>window.LUNAR_VISUAL_SNIPPETS = " . json_encode($snippets, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) . ";</script>";
            // Injeta o modal Blade no rodapé do painel administrativo quando há editor
            echo view('visual-snippets::admin.modals.snippet-picker')->render();
        });

        addAction('init', function() {
            if (request()->routeIs('admin.visual-snippets.*')) {
                EditorManager::registerScript(asset('plugins/visual-snippets/js/visual-snippets-editor.js'));
                EditorManager::registerStyle(asset('plugins/visual-snippets/css/visual-snippets-editor.css'));
            } else if(request()->routeIs('admin.*')) {
                EditorManager::registerScript(asset('plugins/visual-snippets/js/visual-snippets-inserter.js'));
                EditorManager::registerStyle(asset('plugins/visual-snippets/css/visual-snippets-inserter.css'));
            }
        });
    }

    /**
     * Registra os shortcodes [vs-template] e [vs-instance].
     */
    protected function registerShortcodes(): void
    {
        // -------------------------------------------------------------
        // SHORTCODE 1: [vs-template] (Sincronizado / Molde estático puro)
        // -------------------------------------------------------------
        ContentHelper::registerShortcode(
            'vs-template',
            function ($attributes, $content) {
                $slug = $attributes['slug'] ?? null;
                if (!$slug) return '';

                $snippet = self::getSnippet($slug);
                if (!$snippet) return '';

                // Retorna o HTML base do molde diretamente
                // return $snippet->content;
                return preg_replace('#\sdata-var="[^"]*"#', "", $snippet->content);
            },
            'Insere um molde visual sincronizado (global)',
            '[vs-template slug="card-depoimento"]',
            [
                'slug' => [
                    'label'       => 'Slug do Snippet Visual',
                    'type'        => 'text',
                    'placeholder' => 'ex: card-depoimento',
                    'required'    => true,
                ],
            ]
        );

        // -------------------------------------------------------------
        // SHORTCODE 2: [vs-instance] (Customizado / Dados locais do post)
        // -------------------------------------------------------------
        ContentHelper::registerShortcode(
            'vs-instance',
            function ($attributes, $content) {
                $slug = $attributes['slug'] ?? null;
                if (!$slug) return '';

                $snippet = self::getSnippet($slug);
                if (!$snippet) return '';

                // Mescla o HTML do molde com os atributos enviados no shortcode
                // (Podemos isolar esse renderizador num Helper limpo depois)
                return visual_snippet_render($snippet, $attributes, $content);
            },
            'Insere um molde visual com preenchimento de dados locais',
            '[vs-instance slug="card-depoimento" titulo="Muito Bom!" foto="/img.jpg"]',
            [
                'slug' => [
                    'label'       => 'Slug do Snippet Base',
                    'type'        => 'text',
                    'placeholder' => 'ex: card-depoimento',
                    'required'    => true,
                ],
            ]
        );
    }

    static function getSnippet(string $slug) : ?VisualSnippet
    {
        static $instanceCache = [];
        if (!array_key_exists($slug, $instanceCache)) {
            $instanceCache[$slug] = VisualSnippet::active()->where('slug', $slug)->first();
        }
        return $instanceCache[$slug] ?? null;
    }
}

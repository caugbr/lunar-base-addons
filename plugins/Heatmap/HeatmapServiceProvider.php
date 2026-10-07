<?php

namespace Plugins\Heatmap;

use Illuminate\Support\ServiceProvider;
use App\Support\AdminMenu;
use App\Support\Settings;

class HeatmapServiceProvider extends ServiceProvider
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
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'heatmap');

        // Injeta menu na administração
        $menuAfterItem = config('pluginSettings.Heatmap.menuAfterItem', 'Configurações');
        $menuSet = config('pluginSettings.Heatmap.menuSet', 1);

        AdminMenu::add([
            'label'  => 'Heatmap',
            'icon'   => 'flame',
            'route'  => 'admin.heatmap.index',
            'active' => 'admin.heatmap.*',
            'role'   => 'admin',
        ], $menuAfterItem, $menuSet);

        // Injeta os parâmetros de configuração
        Settings::add([
            'type'  => 'subtitle',
            'icon'  => 'flame',
            'label' => 'Configurações do Heatmap',
        ], 'general');

        Settings::add([
            'key'         => 'heatmap_tracked_selectors',
            'type'        => 'text',
            'label'       => 'Seletores monitorados no Heatmap (Whitelist)',
            'description' => 'Elementos interativos que serão rastreados, separados por vírgula. Padrão: a, button, input, select, textarea, label, summary, [role="button"], [role="tab"], [data-track], [data-action], img',
            'default'     => 'a, button, input, select, textarea, label, summary, [role="button"], [role="tab"], [data-track], [data-action], img',
        ], 'general');

        Settings::add([
            'key'         => 'heatmap_hover_delay',
            'type'        => 'number',
            'label'       => 'Tempo de pausa para atenção (Hover)',
            'description' => 'Tempo em segundos que o cursor deve repousar sobre o elemento para registrar interesse. Padrão: 1.2',
            'default'     => 1.2,
            'attributes'  => ['min' => 0.5, 'max' => 5, 'step' => 0.1],
        ], 'general');

        // Espera o sistema carregar para usar auth() - antes disso não funciona direito
        addAction('init', function() {
            // Injeta o rastreador no site público de forma elegante via AssetManager
            if (!request()->is('admin*') && !request()->is('api*')) {
                if (!auth()->check()) {
                    $selectors = setting('general.heatmap_tracked_selectors', 'a, button, input, select, textarea, label, summary, [role="button"], [role="tab"], [data-track], [data-action], img');
                    $hoverDelay = (float) setting('general.heatmap_hover_delay', 1.2) * 1000; // Converte para ms

                    // Injeta a variável JS com a lista de seletores autorizados
                    addInlineScript(
                        "window.__heatmapTrackedSelectors = '{$selectors}';\n" .
                        "window.__heatmapHoverDelay = {$hoverDelay};\n"
                    );

                    // Enfileira o arquivo JS do rastreador com defer e no rodapé
                    addScript(
                        'heatmap-tracker',
                        asset('plugins/heatmap/js/tracker.js'),
                        [],
                        '1.0.0',
                        true, // inFooter
                        true  // defer
                    );
                }
            }
        });
    }
}

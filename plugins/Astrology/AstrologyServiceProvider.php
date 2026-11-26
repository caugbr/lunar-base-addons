<?php

namespace Plugins\Astrology;

use App\Helpers\ContentHelper;
use Illuminate\Support\ServiceProvider;
use Plugins\Astrology\Models\NatalChart;
use Plugins\Astrology\Console\Commands\SyncThemesCommand;

class AstrologyServiceProvider extends ServiceProvider
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
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'astrology');

        ContentHelper::registerShortcode(
            'natal_chart',
            function ($attributes) {
                $slug = $attributes['slug'] ?? null;
                if (!$slug) {
                    return '<!-- [natal_chart] requer o atributo slug -->';
                }

                $chart = NatalChart::where('slug', $slug)
                    ->where('is_public', true)
                    ->first();

                if (!$chart) {
                    return '';
                }

                return view('astrology::public.embed', [
                    'chart' => $chart,
                    'theme' => $attributes['theme'] ?? 'classic',
                ])->render();
            },
            'Renderiza um mapa astral calculado',
            '[natal_chart slug="meu-mapa" theme="classic"]',
            [
                'slug' => [
                    'label'       => 'Slug do mapa cadastrado',
                    'type'        => 'text',
                    'placeholder' => 'slug-do-mapa',
                ],
                'theme' => [
                    'label'       => 'Tema visual da mandala',
                    'type'        => 'text',
                    'placeholder' => 'classic',
                ],
            ]
        );

        if (!function_exists('renderNatalChart')) {
            function renderNatalChart(string $slug, string $theme = 'classic'): string
            {
                $chart = NatalChart::where('slug', $slug)
                    ->where('is_public', true)
                    ->first();

                if (!$chart) {
                    return '';
                }

                return view('astrology::public.embed', compact('chart', 'theme'))->render();
            }
        }

        $menuAfterItem = config('pluginSettings.Astrology.menuAfterItem', 'Taxonomias');
        $menuSet = config('pluginSettings.Astrology.menuSet', 1);

        \App\Support\AdminMenu::add([
            'label'      => 'Mapas Astrais',
            'icon'       => 'compass',
            'route'      => 'admin.astrology.charts.index',
            'active'     => 'admin.astrology.*',
            'permission' => 'manage-pages',
        ], $menuAfterItem, $menuSet);

        \App\Support\AdminMenu::addSubItem('Mapas Astrais', [
            'label'  => 'Novo Mapa',
            'icon'   => 'circle-plus',
            'route'  => 'admin.astrology.charts.create',
            'active' => 'admin.astrology.charts.create',
            'role'   => 'admin',
        ]);

        addRole(
            slug: 'astrologer',
            name: 'Astrólogo',
            description: 'Gerencia mapas e pode ter seus próprios temas.',
            permissions: [ 'view-dashboard', 'manage-charts' ]
        );

        addPermission('manage-charts', 'Gerenciar mapas astrológicos');
        assignPermissionsToRole('admin', 'manage-charts');

        // Comando artisan para sincronizar temas e themes.json
        if ($this->app->runningInConsole()) {
            $this->commands([
                SyncThemesCommand::class,
            ]);
        }

        // Registra o componente da grade triangular de aspectos
        \Illuminate\Support\Facades\Blade::component('astrology::components.aspect-grid', 'astrology-aspect-grid');
        \Illuminate\Support\Facades\Blade::component('astrology::components.sign-icon', 'astrology-sign-icon');
        \Illuminate\Support\Facades\Blade::component('astrology::components.position', 'astrology-position');
    }
}

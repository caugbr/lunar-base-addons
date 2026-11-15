<?php

use Illuminate\Support\Facades\Route;
use Plugins\Astrology\Http\Controllers\NatalChartController;
use Plugins\Astrology\Http\Controllers\BiwheelChartController;

// Rotas Administrativas
Route::middleware(['web', 'auth', 'role:admin,editor'])
    ->prefix('admin/astrology')
    ->name('admin.astrology.')
    ->group(function () {
        // CRUD de Mapas Astrais
        Route::resource('charts', NatalChartController::class);

        // Ação rápida: Recalcular efemérides de um mapa existente
        Route::post('charts/{chart}/recalculate', [NatalChartController::class, 'recalculate'])
            ->name('charts.recalculate');

        // Baixar o SVG compilado
        Route::get('charts/{chart}/download-svg', [NatalChartController::class, 'downloadSvg'])
            ->name('charts.download-svg');

        Route::get('biwheel', [BiwheelChartController::class, 'index'])
            ->name('biwheel.index');

        Route::get('biwheel/download-svg', [BiwheelChartController::class, 'downloadSvg'])
            ->name('biwheel.download-svg');

        Route::get('charts/{chart}/solar-return', [NatalChartController::class, 'solarReturn'])
            ->name('charts.solar-return');

        Route::get('charts/{chart}/solar-return/download-svg', [NatalChartController::class, 'solarReturnDownloadSvg'])
            ->name('charts.solar-return.download-svg');
    });

// Rotas Públicas e API
Route::middleware(['web'])
    ->prefix('astrology')
    ->name('astrology.')
    ->group(function () {
        // Exibição pública de um mapa via slug
        Route::get('chart/{slug}', [NatalChartController::class, 'publicShow'])
            ->name('public.show');

        // Endpoint JSON para componentes Vue/AJAX consumirem os dados do mapa
        Route::get('api/chart/{chart:slug}', [NatalChartController::class, 'apiData'])
            ->name('api.chart');
    });

<?php

use Illuminate\Support\Facades\Route;
use Plugins\Astrology\Http\Controllers\NatalChartController;
use Plugins\Astrology\Http\Controllers\BiwheelChartController;
use Plugins\Astrology\Http\Controllers\PublicChartController;

// Rotas Administrativas
Route::middleware(['web', 'auth', 'role:admin,editor,astrologer', 'permission:manage-charts'])
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
        // Calculadora pública simplificada
        Route::get('calculadora', [PublicChartController::class, 'index'])
            ->name('calculator.index');

        Route::post('calculadora', [PublicChartController::class, 'calculate'])
            ->middleware('throttle:20,1')
            ->name('calculator.calculate');

        Route::get('calculadora/download-svg/{token}', [PublicChartController::class, 'downloadSvg'])
            ->name('calculator.download-svg');

        // Exibição pública de um mapa via slug
        Route::get('chart/{slug}', [NatalChartController::class, 'publicShow'])
            ->name('public.show');

        // Endpoint JSON para componentes Vue/AJAX consumirem os dados do mapa
        Route::get('api/chart/{chart:slug}', [NatalChartController::class, 'apiData'])
            ->name('api.chart');

        Route::match(['get', 'post'], 'calculadora/clock', [PublicChartController::class, 'astroClock'])
            ->middleware('throttle:20,1')
            ->name('calculator.sky-now');
    });

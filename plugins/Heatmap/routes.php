<?php

use Illuminate\Support\Facades\Route;
use Plugins\Heatmap\Http\Controllers\HeatmapApiController;
use Plugins\Heatmap\Http\Controllers\HeatmapAdminController;

// Rota pública de coleta (sem CSRF para permitir sendBeacon)
Route::post('/api/heatmap/track', [HeatmapApiController::class, 'track']);

// Rotas do Painel Administrativo
Route::middleware(['web', 'auth', 'permission:manage-settings'])->prefix('admin/heatmap')->name('admin.heatmap.')->group(function () {
    Route::get('/', [HeatmapAdminController::class, 'index'])->name('index');
    Route::get('/view', [HeatmapAdminController::class, 'show'])->name('show');
    Route::get('/data', [HeatmapAdminController::class, 'data'])->name('data');
    Route::delete('/clear', [HeatmapAdminController::class, 'clear'])->name('clear');
});

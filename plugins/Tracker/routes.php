
<?php

use Illuminate\Support\Facades\Route;
use Plugins\Tracker\Http\Controllers\TrackerController;

// Rota pública para captura de eventos via Beacon/Fetch
Route::post('/tracker/api/event', [TrackerController::class, 'recordEvent'])->name('tracker.api.event');

// Rotas Administrativas do Dashboard
Route::middleware(['web', 'auth'])
    ->prefix('admin/tracker')
    ->name('admin.tracker.')
    ->group(function () {
        Route::get('/', [TrackerController::class, 'index'])->name('index');
        Route::get('/hourly', [TrackerController::class, 'hourly'])->name('hourly');
        Route::get('/pages', [TrackerController::class, 'pages'])->name('pages');
        Route::get('/referrers', [TrackerController::class, 'referrers'])->name('referrers');
        Route::get('/events', [TrackerController::class, 'events'])->name('events'); // Relatório completo de eventos
    });

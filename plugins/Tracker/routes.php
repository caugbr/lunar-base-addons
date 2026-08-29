<?php

use Illuminate\Support\Facades\Route;
use Plugins\Tracker\Http\Controllers\TrackerController;

Route::middleware(['web', 'auth'])
    ->prefix('admin/tracker')
    ->name('admin.tracker.')
    ->group(function () {
        Route::get('/', [TrackerController::class, 'index'])->name('index');
    });

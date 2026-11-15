<?php

use Illuminate\Support\Facades\Route;
use Plugins\Cities\Http\Controllers\CityController;

Route::middleware(['web', 'throttle:60,1'])
    ->prefix('api/cities')
    ->name('cities.')
    ->group(function () {
        Route::get('search', [CityController::class, 'search'])->name('search');
    });

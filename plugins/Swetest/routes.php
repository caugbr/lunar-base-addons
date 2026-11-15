<?php

use Illuminate\Support\Facades\Route;
use Plugins\Swetest\Http\Controllers\SwetestApiController;

Route::prefix('api/swetest')
    ->name('swetest.api.')
    ->group(function () {
        Route::post('calculate', [SwetestApiController::class, 'calculate'])
            ->name('calculate');
    });

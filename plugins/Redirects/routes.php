<?php

use Illuminate\Support\Facades\Route;
use Plugins\Redirects\Http\Controllers\AdminRedirectController;
use Plugins\Redirects\Http\Controllers\AdminNotFoundController;

Route::middleware(['web', 'auth', 'role:admin'])->prefix('admin/redirects')->name('admin.redirects.')->group(function () {
    Route::get('/', [AdminRedirectController::class, 'index'])->name('index');
    Route::post('/', [AdminRedirectController::class, 'store'])->name('store');
    Route::delete('/{id}', [AdminRedirectController::class, 'destroy'])->name('destroy');

    Route::get('/404', [AdminNotFoundController::class, 'index'])->name('404');
    Route::post('/404/{id}/convert', [AdminNotFoundController::class, 'convertToRedirect'])->name('404.convert');
    Route::delete('/404/{id}', [AdminNotFoundController::class, 'destroy'])->name('404.destroy');
});

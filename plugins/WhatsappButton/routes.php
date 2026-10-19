<?php

use Illuminate\Support\Facades\Route;
use Plugins\WhatsAppButton\Http\Controllers\WhatsAppButtonController;

Route::middleware(['web', 'auth', 'role:admin'])
    ->prefix('admin')
    ->name('admin.whatsapp-button.')
    ->group(function () {
        Route::get('whatsapp-button', [WhatsAppButtonController::class, 'index'])->name('index');
        Route::post('whatsapp-button', [WhatsAppButtonController::class, 'update'])->name('update');
    });

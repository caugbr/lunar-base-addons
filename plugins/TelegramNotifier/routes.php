<?php

use Illuminate\Support\Facades\Route;
use Plugins\TelegramNotifier\Http\Controllers\TelegramNotifierController;

Route::middleware(['web', 'auth', 'role:admin'])
    ->prefix('admin')
    ->name('admin.telegram-notifier.')
    ->group(function () {
        Route::get('telegram-notifier', [TelegramNotifierController::class, 'index'])->name('index');
        Route::post('telegram-notifier', [TelegramNotifierController::class, 'update'])->name('update');
        Route::post('telegram-notifier/test', [TelegramNotifierController::class, 'test'])->name('test');
    });

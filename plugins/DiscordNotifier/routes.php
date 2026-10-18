<?php

use Illuminate\Support\Facades\Route;
use Plugins\DiscordNotifier\Http\Controllers\DiscordNotifierController;

Route::middleware(['web', 'auth', 'role:admin'])
    ->prefix('admin')
    ->name('admin.discord-notifier.')
    ->group(function () {
        Route::get('discord-notifier', [DiscordNotifierController::class, 'index'])->name('index');
        Route::post('discord-notifier', [DiscordNotifierController::class, 'update'])->name('update');
        Route::post('discord-notifier/test', [DiscordNotifierController::class, 'test'])->name('test');
    });

<?php

use Illuminate\Support\Facades\Route;
use Plugins\UserSwitcher\Http\Controllers\ImpersonateController;

Route::middleware(['web', 'auth'])->prefix('admin/user-switcher')->name('admin.user-switcher.')->group(function () {
    // Iniciar a simulação de um usuário
    Route::post('/start/{user}', [ImpersonateController::class, 'start'])->name('start');

    // Sair da simulação e voltar ao admin original
    Route::post('/stop', [ImpersonateController::class, 'stop'])->name('stop');
});

<?php

use Illuminate\Support\Facades\Route;
use Plugins\RoleSwitcher\Http\Controllers\MatrixController;
use Plugins\RoleSwitcher\Http\Controllers\SwitchController;

Route::middleware(['web', 'auth'])->prefix('admin/role-switcher')->name('admin.role-switcher.')->group(function () {
    // Matriz de Configuração (somente admin com permissão)
    Route::get('/matrix', [MatrixController::class, 'index'])->name('matrix')->middleware('permission:manage-settings');
    Route::post('/matrix', [MatrixController::class, 'update'])->name('matrix.update')->middleware('permission:manage-settings');

    // Ações de Alternância (qualquer usuário com papéis autorizados na matriz)
    Route::post('/switch', [SwitchController::class, 'switch'])->name('switch');
    Route::post('/reset', [SwitchController::class, 'reset'])->name('reset');
});

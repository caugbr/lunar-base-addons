<?php

use Illuminate\Support\Facades\Route;
use Plugins\ArchiveVault\Http\Controllers\ArchiveVaultController;

Route::middleware(['web', 'auth'])
    ->prefix('admin/tools/archive-vault')
    ->name('admin.archive_vault.')
    ->group(function () {
        Route::get('/', [ArchiveVaultController::class, 'index'])->name('index');
        Route::get('/{record}', [ArchiveVaultController::class, 'show'])->name('show');
        Route::post('/{record}/restore', [ArchiveVaultController::class, 'restore'])->name('restore');
        Route::delete('/{record}/destroy', [ArchiveVaultController::class, 'destroy'])->name('destroy');
    });

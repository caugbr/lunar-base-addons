<?php

use Illuminate\Support\Facades\Route;
use Plugins\Backup\Http\Controllers\BackupController;

Route::middleware(['web', 'auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('backups', [BackupController::class, 'index'])->name('backups.index');
    Route::post('backups', [BackupController::class, 'store'])->name('backups.store');
    Route::post('backups/import', [BackupController::class, 'import'])->name('backups.import');
    Route::get('backups/download/{filename}', [BackupController::class, 'download'])->name('backups.download');
    Route::post('backups/restore/{filename}', [BackupController::class, 'restore'])->name('backups.restore');
    Route::delete('backups/{filename}', [BackupController::class, 'destroy'])->name('backups.destroy');
});

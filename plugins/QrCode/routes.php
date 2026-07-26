<?php

use Illuminate\Support\Facades\Route;
use Plugins\QrCode\Http\Controllers\QrCodeController;

Route::middleware(['web', 'auth', 'role:admin,editor'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('qrcode', [QrCodeController::class, 'index'])->name('qrcode.index');
        Route::post('qrcode/download', [QrCodeController::class, 'download'])->name('qrcode.download');
    });

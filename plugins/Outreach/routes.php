<?php

use Illuminate\Support\Facades\Route;
use Plugins\Outreach\Http\Controllers\ClientController;
use Plugins\Outreach\Http\Controllers\TemplateController;
use Plugins\Outreach\Http\Controllers\DispatchController;

Route::middleware(['web', 'auth', 'role:admin,editor'])
    ->prefix('admin/outreach')
    ->name('admin.outreach.')
    ->group(function () {
        // Contatos (CRUD + Anotações)
        Route::resource('clients', ClientController::class);
        Route::post('clients/{client}/notes', [ClientController::class, 'storeNote'])->name('clients.notes.store');
        Route::delete('clients/{client}/notes/{note}', [ClientController::class, 'destroyNote'])->name('clients.notes.destroy');

        // Modelos de E-mail (CRUD)
        Route::resource('templates', TemplateController::class);

        // Disparo de E-mails em Lote
        Route::post('dispatch/preview', [DispatchController::class, 'preview'])->name('dispatch.preview');
        Route::post('dispatch/send', [DispatchController::class, 'send'])->name('dispatch.send');
    });

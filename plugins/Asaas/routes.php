<?php

use Illuminate\Support\Facades\Route;
use Plugins\Asaas\Http\Controllers\AsaasWebhookController;
use Plugins\Asaas\Http\Controllers\AsaasAdminController;

// ROTA PÚBLICA DE WEBHOOK
Route::prefix('api/v1/asaas')->group(function () {
    Route::post('webhook', [AsaasWebhookController::class, 'handle'])->name('asaas.webhook');
    Route::get('success', [AsaasWebhookController::class, 'success'])->name('asaas.success');
});

// ROTAS ADMINISTRATIVAS
Route::middleware(['web', 'auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('asaas/invoices', [AsaasAdminController::class, 'index'])->name('asaas.invoices.index');
    Route::get('asaas/invoices/{id}', [AsaasAdminController::class, 'show'])->name('asaas.invoices.show');
});

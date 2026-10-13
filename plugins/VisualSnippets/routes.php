<?php

use Illuminate\Support\Facades\Route;
use Plugins\VisualSnippets\Http\Controllers\VisualSnippetController;

// Rotas Administrativas do Plugin
Route::middleware(['web', 'auth', 'role:admin,editor'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        // CRUD Completo: index, create, store, show, edit, update, destroy
        Route::resource('visual-snippets', VisualSnippetController::class);
    });

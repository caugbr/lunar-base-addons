<?php

use Illuminate\Support\Facades\Route;
use Plugins\Sitemap\Http\Controllers\PublicSitemapController;
use Plugins\Sitemap\Http\Controllers\AdminSitemapController;

// Rota pública do Sitemap XML
Route::get('/sitemap.xml', [PublicSitemapController::class, 'generate'])->name('sitemap.xml');

// Rota administrativa
Route::middleware(['web', 'auth', 'role:admin'])->prefix('admin/sitemap')->name('admin.sitemap.')->group(function () {
    Route::get('/', [AdminSitemapController::class, 'index'])->name('index');
});

<?php

use Illuminate\Support\Facades\Route;
use Plugins\FrontendLogin\Http\Controllers\FrontendLoginController;

Route::post('frontend-login', [FrontendLoginController::class, 'login'])
    ->middleware('web')
    ->name('frontend-login.submit');

Route::post('frontend-logout', [FrontendLoginController::class, 'logout'])
    ->middleware('web')
    ->name('frontend-login.logout');

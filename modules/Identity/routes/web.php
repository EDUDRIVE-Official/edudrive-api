<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Identity\Presentation\Http\Controllers\LoginWebController;
use Modules\Identity\Presentation\Http\Controllers\LogoutWebController;

Route::middleware('web')->group(function (): void {
    Route::middleware('guest')->group(function (): void {
        Route::get('/login', [LoginWebController::class, 'create'])->name('login');
        Route::post('/login', [LoginWebController::class, 'store'])->middleware('throttle:login')->name('login.attempt');
    });

    Route::middleware('auth')->group(function (): void {
        Route::post('/logout', LogoutWebController::class)->name('logout');
    });
});

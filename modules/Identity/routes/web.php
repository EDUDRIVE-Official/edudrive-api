<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Identity\Presentation\Http\Controllers\LoginWebController;
use Modules\Identity\Presentation\Http\Controllers\LogoutWebController;
use Modules\Identity\Presentation\Http\Controllers\UserWebController;

Route::middleware('web')->group(function (): void {
    Route::middleware('guest')->group(function (): void {
        Route::get('/login', [LoginWebController::class, 'create'])->name('login');
        Route::post('/login', [LoginWebController::class, 'store'])->middleware('throttle:login')->name('login.attempt');
    });

    Route::middleware('auth')->group(function (): void {
        Route::post('/logout', LogoutWebController::class)->name('logout');

        Route::middleware('permission:users.view')->group(function (): void {
            Route::get('/users', [UserWebController::class, 'index'])->name('users.index');
        });

        Route::middleware('permission:users.manage')->group(function (): void {
            Route::post('/users/{userId}/activate', [UserWebController::class, 'activate'])
                ->whereUuid('userId')
                ->name('users.activate');

            Route::post('/users/{userId}/deactivate', [UserWebController::class, 'deactivate'])
                ->whereUuid('userId')
                ->name('users.deactivate');
        });
    });
});

<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Identity\Presentation\Http\Controllers\ActivateUserController;
use Modules\Identity\Presentation\Http\Controllers\AuthController;
use Modules\Identity\Presentation\Http\Controllers\LoginController;

Route::middleware('api')
    ->prefix('api/v1/auth')
    ->group(function (): void {
        Route::post('/register', [AuthController::class, 'register'])
            ->name('api.v1.auth.register');

        Route::post('/login', LoginController::class)
            ->name('api.v1.auth.login');

        Route::post('/users/{userId}/activate', ActivateUserController::class)
            ->whereUuid('userId')
            ->name('api.v1.auth.users.activate');
    });

<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Authorization\Presentation\Http\Controllers\AuthorizationStatusController;

Route::prefix('api/v1/authorization')
    ->name('api.v1.authorization.')
    ->group(function (): void {
        Route::get('/status', AuthorizationStatusController::class)
            ->name('status');
    });

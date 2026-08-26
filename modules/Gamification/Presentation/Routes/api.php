<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Gamification\Presentation\Http\Controllers\GamificationStatusController;

Route::prefix('api/v1/gamification')
    ->name('api.v1.gamification.')
    ->group(function (): void {
        Route::get('/status', GamificationStatusController::class)
            ->name('status');
    });

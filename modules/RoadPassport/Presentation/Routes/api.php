<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\RoadPassport\Presentation\Http\Controllers\RoadPassportStatusController;

Route::prefix('api/v1/road-passport')
    ->name('api.v1.road-passport.')
    ->group(function (): void {
        Route::get('/status', RoadPassportStatusController::class)
            ->name('status');
    });

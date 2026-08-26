<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\RoadPassport\Presentation\Http\Controllers\RoadPassportController;
use Modules\RoadPassport\Presentation\Http\Controllers\RoadPassportStatusController;

Route::prefix('api/v1/road-passport')
    ->name('api.v1.road-passport.')
    ->group(function (): void {
        Route::get('/status', RoadPassportStatusController::class)
            ->name('status');

        Route::middleware('auth:sanctum')->group(function (): void {
            Route::get('/me', [RoadPassportController::class, 'me'])
                ->name('me');

            Route::get('/{roadPassportId}', [RoadPassportController::class, 'show'])
                ->whereUuid('roadPassportId')
                ->name('show');

            Route::middleware('permission:road_passports.manage')->group(function (): void {
                Route::post('/', [RoadPassportController::class, 'store'])
                    ->name('store');

                Route::post('/{roadPassportId}/suspend', [RoadPassportController::class, 'suspend'])
                    ->whereUuid('roadPassportId')
                    ->name('suspend');

                Route::post('/{roadPassportId}/reactivate', [RoadPassportController::class, 'reactivate'])
                    ->whereUuid('roadPassportId')
                    ->name('reactivate');

                Route::post('/{roadPassportId}/revoke', [RoadPassportController::class, 'revoke'])
                    ->whereUuid('roadPassportId')
                    ->name('revoke');

                Route::put('/{roadPassportId}/level', [RoadPassportController::class, 'changeLevel'])
                    ->whereUuid('roadPassportId')
                    ->name('level.update');
            });
        });
    });

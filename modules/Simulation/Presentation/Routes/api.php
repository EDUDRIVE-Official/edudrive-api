<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Simulation\Presentation\Http\Controllers\SimulationStatusController;
use Modules\Simulation\Presentation\Http\Controllers\SimulatorController;

Route::prefix('api/v1/simulation')
    ->name('api.v1.simulation.')
    ->group(function (): void {
        Route::get('/status', SimulationStatusController::class)
            ->name('status');

        Route::middleware('auth:sanctum')->group(function (): void {
            Route::middleware('permission:simulators.view')->group(function (): void {
                Route::get('/simulators', [SimulatorController::class, 'index'])
                    ->name('simulators.index');

                Route::get('/simulators/{simulatorId}', [SimulatorController::class, 'show'])
                    ->whereUuid('simulatorId')
                    ->name('simulators.show');
            });

            Route::middleware('permission:simulators.manage')->group(function (): void {
                Route::post('/simulators', [SimulatorController::class, 'store'])
                    ->name('simulators.store');

                Route::post('/simulators/{simulatorId}/suspend', [SimulatorController::class, 'suspend'])
                    ->whereUuid('simulatorId')
                    ->name('simulators.suspend');

                Route::post('/simulators/{simulatorId}/reactivate', [SimulatorController::class, 'reactivate'])
                    ->whereUuid('simulatorId')
                    ->name('simulators.reactivate');

                Route::post('/simulators/{simulatorId}/retire', [SimulatorController::class, 'retire'])
                    ->whereUuid('simulatorId')
                    ->name('simulators.retire');

                Route::post('/simulators/{simulatorId}/rotate-key', [SimulatorController::class, 'rotateKey'])
                    ->whereUuid('simulatorId')
                    ->name('simulators.rotate-key');
            });
        });
    });

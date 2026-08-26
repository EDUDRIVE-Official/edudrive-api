<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Simulation\Presentation\Http\Controllers\DecisionEngineController;
use Modules\Simulation\Presentation\Http\Controllers\SimulationSessionController;
use Modules\Simulation\Presentation\Http\Controllers\SimulationStatusController;
use Modules\Simulation\Presentation\Http\Controllers\SimulatorController;
use Modules\Simulation\Presentation\Http\Controllers\TelemetryController;

Route::prefix('api/v1/simulation')
    ->name('api.v1.simulation.')
    ->group(function (): void {
        Route::get('/status', SimulationStatusController::class)
            ->name('status');

        Route::post('/sessions/{sessionId}/telemetry', [TelemetryController::class, 'store'])
            ->whereUuid('sessionId')
            ->middleware('simulator.auth')
            ->name('sessions.telemetry.store');

        Route::post('/sessions/{sessionId}/decisions', [DecisionEngineController::class, 'store'])
            ->whereUuid('sessionId')
            ->middleware('simulator.auth')
            ->name('sessions.decisions.store');

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

            Route::post('/sessions', [SimulationSessionController::class, 'store'])
                ->name('sessions.store');

            Route::get('/sessions/me', [SimulationSessionController::class, 'me'])
                ->name('sessions.me');

            Route::get('/sessions/{sessionId}', [SimulationSessionController::class, 'show'])
                ->whereUuid('sessionId')
                ->name('sessions.show');

            Route::post('/sessions/{sessionId}/start', [SimulationSessionController::class, 'start'])
                ->whereUuid('sessionId')
                ->name('sessions.start');

            Route::post('/sessions/{sessionId}/complete', [SimulationSessionController::class, 'complete'])
                ->whereUuid('sessionId')
                ->name('sessions.complete');

            Route::post('/sessions/{sessionId}/cancel', [SimulationSessionController::class, 'cancel'])
                ->whereUuid('sessionId')
                ->name('sessions.cancel');

            Route::get('/sessions/{sessionId}/telemetry', [TelemetryController::class, 'show'])
                ->whereUuid('sessionId')
                ->name('sessions.telemetry.show');

            Route::get('/sessions/{sessionId}/result', [SimulationSessionController::class, 'result'])
                ->whereUuid('sessionId')
                ->name('sessions.result');

            Route::get('/sessions/{sessionId}/decisions', [DecisionEngineController::class, 'show'])
                ->whereUuid('sessionId')
                ->name('sessions.decisions.show');

            Route::middleware('permission:simulation_sessions.view')->group(function (): void {
                Route::get('/sessions', [SimulationSessionController::class, 'index'])
                    ->name('sessions.index');
            });
        });
    });

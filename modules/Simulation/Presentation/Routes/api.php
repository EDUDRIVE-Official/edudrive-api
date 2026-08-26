<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Simulation\Presentation\Http\Controllers\SimulationStatusController;

Route::prefix('api/v1/simulation')
    ->name('api.v1.simulation.')
    ->group(function (): void {
        Route::get('/status', SimulationStatusController::class)
            ->name('status');
    });

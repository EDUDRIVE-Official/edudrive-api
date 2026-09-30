<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Simulation\Presentation\Http\Controllers\SimulationWebController;

Route::middleware(['web', 'auth'])->group(function (): void {
    Route::get('/mis-simulaciones', [SimulationWebController::class, 'mine'])
        ->name('simulations.mine');

    Route::get('/admin/reportes-simulacion', [SimulationWebController::class, 'reports'])
        ->middleware('permission:reports.view')
        ->name('simulations.reports');
});

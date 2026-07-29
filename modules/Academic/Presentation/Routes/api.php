<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Academic\Presentation\Http\Controllers\AcademicStatusController;

Route::prefix('api/v1/academic')
    ->name('api.v1.academic.')
    ->group(function (): void {
        Route::get('/status', AcademicStatusController::class)
            ->name('status');
    });

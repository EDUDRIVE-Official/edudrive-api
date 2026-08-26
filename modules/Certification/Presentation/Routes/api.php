<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Certification\Presentation\Http\Controllers\CertificationStatusController;

Route::prefix('api/v1/certification')
    ->name('api.v1.certification.')
    ->group(function (): void {
        Route::get('/status', CertificationStatusController::class)
            ->name('status');
    });

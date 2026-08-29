<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Analytics\Presentation\Http\Controllers\AnalyticsReportController;

Route::prefix('api/v1/analytics')
    ->name('api.v1.analytics.')
    ->middleware(['auth:sanctum', 'permission:analytics.view'])
    ->group(function (): void {
        Route::post('/reports', [AnalyticsReportController::class, 'store'])
            ->name('reports.store');
    });

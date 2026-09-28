<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Analytics\Presentation\Http\Controllers\AnalyticsWebController;

Route::middleware(['web', 'auth', 'permission:analytics.view'])->prefix('admin/analitica')->name('analytics.')->group(function (): void {
    Route::get('/', [AnalyticsWebController::class, 'index'])->name('index');
    Route::post('/reportes', [AnalyticsWebController::class, 'store'])->name('reports.store');
    Route::get('/reportes/{asyncJobId}', [AnalyticsWebController::class, 'show'])->whereUuid('asyncJobId')->name('reports.show');
});

<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Learning\Presentation\Http\Controllers\LearningEventController;

Route::prefix('api/v1/academic')
    ->name('api.v1.academic.')
    ->group(function (): void {
        Route::middleware('auth:sanctum')->group(function (): void {
            Route::get('/enrollments/{enrollmentId}/learning-events', [LearningEventController::class, 'index'])
                ->whereUuid('enrollmentId')
                ->name('enrollments.learning-events.index');
        });
    });

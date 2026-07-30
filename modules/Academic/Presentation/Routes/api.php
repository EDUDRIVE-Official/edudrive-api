<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Academic\Presentation\Http\Controllers\AcademicStatusController;
use Modules\Academic\Presentation\Http\Controllers\CourseController;

Route::prefix('api/v1/academic')
    ->name('api.v1.academic.')
    ->group(function (): void {
        Route::get('/status', AcademicStatusController::class)
            ->name('status');

        Route::get('/courses', [CourseController::class, 'index'])
            ->name('courses.index');

        Route::post('/courses', [CourseController::class, 'store'])
            ->name('courses.store');
    });

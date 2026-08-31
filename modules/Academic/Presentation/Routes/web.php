<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Academic\Presentation\Http\Controllers\CourseWebController;

Route::middleware(['web', 'auth'])->group(function (): void {
    Route::middleware('permission:courses.view')->group(function (): void {
        Route::get('/courses', [CourseWebController::class, 'index'])
            ->name('courses.index');
    });

    Route::middleware('permission:courses.manage')->group(function (): void {
        Route::get('/courses/create', [CourseWebController::class, 'create'])
            ->name('courses.create');

        Route::post('/courses', [CourseWebController::class, 'store'])
            ->name('courses.store');

        Route::post('/courses/{courseId}/submit-for-review', [CourseWebController::class, 'submitForReview'])
            ->whereUuid('courseId')
            ->name('courses.submitForReview');

        Route::post('/courses/{courseId}/approve', [CourseWebController::class, 'approve'])
            ->whereUuid('courseId')
            ->name('courses.approve');

        Route::post('/courses/{courseId}/publish', [CourseWebController::class, 'publish'])
            ->whereUuid('courseId')
            ->name('courses.publish');

        Route::post('/courses/{courseId}/archive', [CourseWebController::class, 'archive'])
            ->whereUuid('courseId')
            ->name('courses.archive');
    });
});

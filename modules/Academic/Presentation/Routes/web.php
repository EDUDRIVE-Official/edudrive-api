<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Academic\Presentation\Http\Controllers\CourseWebController;

Route::middleware(['web', 'auth'])->group(function (): void {
    Route::middleware('permission:courses.view')->group(function (): void {
        Route::get('/courses', [CourseWebController::class, 'index'])
            ->name('courses.index');
    });
});

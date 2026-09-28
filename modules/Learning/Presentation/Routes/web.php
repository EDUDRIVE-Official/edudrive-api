<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Learning\Presentation\Http\Controllers\LearningEventWebController;

Route::middleware(['web', 'auth'])->group(function (): void {
    Route::get('/matriculas/{enrollmentId}/actividad', LearningEventWebController::class)
        ->whereUuid('enrollmentId')
        ->name('learning-events.show');
});

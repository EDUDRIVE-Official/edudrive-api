<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Gamification\Presentation\Http\Controllers\GamificationWebController;

Route::middleware(['web', 'auth'])->group(function (): void {
    Route::get('/mi-progreso', GamificationWebController::class)
        ->name('gamification.dashboard');
    Route::post('/mi-progreso/retos/{challengeId}/unirse', [GamificationWebController::class, 'join'])
        ->whereUuid('challengeId')
        ->name('gamification.challenges.join');
});

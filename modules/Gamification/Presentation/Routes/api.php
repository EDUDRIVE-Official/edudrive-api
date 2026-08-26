<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Gamification\Presentation\Http\Controllers\AchievementController;
use Modules\Gamification\Presentation\Http\Controllers\GamificationStatusController;

Route::prefix('api/v1/gamification')
    ->name('api.v1.gamification.')
    ->group(function (): void {
        Route::get('/status', GamificationStatusController::class)
            ->name('status');

        Route::middleware('auth:sanctum')->group(function (): void {
            Route::get('/achievements/me', [AchievementController::class, 'me'])
                ->name('achievements.me');

            Route::middleware('permission:achievements.view')->group(function (): void {
                Route::get('/achievements', [AchievementController::class, 'index'])
                    ->name('achievements.index');

                Route::get('/achievements/{achievementId}', [AchievementController::class, 'show'])
                    ->whereUuid('achievementId')
                    ->name('achievements.show');
            });

            Route::middleware('permission:achievements.manage')->group(function (): void {
                Route::post('/achievements', [AchievementController::class, 'store'])
                    ->name('achievements.store');

                Route::post('/achievements/{achievementId}/retire', [AchievementController::class, 'retire'])
                    ->whereUuid('achievementId')
                    ->name('achievements.retire');

                Route::post('/achievements/{achievementId}/grant', [AchievementController::class, 'grant'])
                    ->whereUuid('achievementId')
                    ->name('achievements.grant');
            });
        });
    });

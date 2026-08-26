<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Gamification\Presentation\Http\Controllers\AchievementController;
use Modules\Gamification\Presentation\Http\Controllers\BadgeController;
use Modules\Gamification\Presentation\Http\Controllers\ChallengeController;
use Modules\Gamification\Presentation\Http\Controllers\ExperienceController;
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

            Route::get('/badges/me', [BadgeController::class, 'me'])
                ->name('badges.me');

            Route::middleware('permission:badges.view')->group(function (): void {
                Route::get('/badges', [BadgeController::class, 'index'])
                    ->name('badges.index');

                Route::get('/badges/{badgeId}', [BadgeController::class, 'show'])
                    ->whereUuid('badgeId')
                    ->name('badges.show');
            });

            Route::middleware('permission:badges.manage')->group(function (): void {
                Route::post('/badges', [BadgeController::class, 'store'])
                    ->name('badges.store');

                Route::put('/badges/{badgeId}', [BadgeController::class, 'update'])
                    ->whereUuid('badgeId')
                    ->name('badges.update');

                Route::post('/badges/{badgeId}/retire', [BadgeController::class, 'retire'])
                    ->whereUuid('badgeId')
                    ->name('badges.retire');

                Route::post('/badges/{badgeId}/grant', [BadgeController::class, 'grant'])
                    ->whereUuid('badgeId')
                    ->name('badges.grant');
            });

            Route::get('/experience/me', [ExperienceController::class, 'me'])
                ->name('experience.me');

            Route::middleware('permission:experience.manage')->group(function (): void {
                Route::post('/experience/grant', [ExperienceController::class, 'grant'])
                    ->name('experience.grant');
            });

            Route::get('/challenges/me', [ChallengeController::class, 'me'])
                ->name('challenges.me');

            Route::middleware('permission:challenges.view')->group(function (): void {
                Route::get('/challenges', [ChallengeController::class, 'index'])
                    ->name('challenges.index');

                Route::get('/challenges/{challengeId}', [ChallengeController::class, 'show'])
                    ->whereUuid('challengeId')
                    ->name('challenges.show');
            });

            Route::middleware('permission:challenges.manage')->group(function (): void {
                Route::post('/challenges', [ChallengeController::class, 'store'])
                    ->name('challenges.store');

                Route::post('/challenges/{challengeId}/retire', [ChallengeController::class, 'retire'])
                    ->whereUuid('challengeId')
                    ->name('challenges.retire');

                Route::post('/challenges/{challengeId}/join', [ChallengeController::class, 'join'])
                    ->whereUuid('challengeId')
                    ->name('challenges.join');

                Route::post('/challenges/{challengeId}/complete', [ChallengeController::class, 'complete'])
                    ->whereUuid('challengeId')
                    ->name('challenges.complete');
            });
        });
    });

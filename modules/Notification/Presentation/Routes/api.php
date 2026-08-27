<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Notification\Presentation\Http\Controllers\NotificationController;
use Modules\Notification\Presentation\Http\Controllers\NotificationPreferenceController;
use Modules\Notification\Presentation\Http\Controllers\NotificationStatusController;

Route::prefix('api/v1/notification')
    ->name('api.v1.notification.')
    ->group(function (): void {
        Route::get('/status', NotificationStatusController::class)
            ->name('status');

        Route::middleware('auth:sanctum')->group(function (): void {
            Route::get('/notifications/me', [NotificationController::class, 'me'])
                ->name('notifications.me');

            Route::post('/notifications/{notificationId}/read', [NotificationController::class, 'markAsRead'])
                ->whereUuid('notificationId')
                ->name('notifications.read');

            Route::middleware('permission:notifications.manage')->group(function (): void {
                Route::post('/notifications', [NotificationController::class, 'store'])
                    ->name('notifications.store');
            });

            Route::get('/preferences/me', [NotificationPreferenceController::class, 'show'])
                ->name('preferences.me');

            Route::put('/preferences/me', [NotificationPreferenceController::class, 'update'])
                ->name('preferences.update');

            Route::post('/preferences/me/consent', [NotificationPreferenceController::class, 'giveConsent'])
                ->name('preferences.consent.give');

            Route::delete('/preferences/me/consent', [NotificationPreferenceController::class, 'revokeConsent'])
                ->name('preferences.consent.revoke');
        });
    });

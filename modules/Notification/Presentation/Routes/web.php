<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Notification\Presentation\Http\Controllers\NotificationAdminWebController;
use Modules\Notification\Presentation\Http\Controllers\NotificationWebController;

Route::middleware(['web', 'auth'])->group(function (): void {
    Route::get('/mis-notificaciones', [NotificationWebController::class, 'index'])
        ->name('notifications.index');
    Route::post('/mis-notificaciones/{notificationId}/leer', [NotificationWebController::class, 'markAsRead'])
        ->whereUuid('notificationId')
        ->name('notifications.read');
    Route::get('/mis-notificaciones/{notificationId}/abrir', [NotificationWebController::class, 'openAction'])
        ->whereUuid('notificationId')
        ->name('notifications.open');
    Route::put('/mis-notificaciones/preferencias', [NotificationWebController::class, 'updatePreferences'])
        ->name('notifications.preferences.update');
    Route::post('/mis-notificaciones/consentimiento', [NotificationWebController::class, 'giveConsent'])
        ->name('notifications.consent.give');
    Route::delete('/mis-notificaciones/consentimiento', [NotificationWebController::class, 'revokeConsent'])
        ->name('notifications.consent.revoke');

    Route::middleware('permission:notifications.manage')->group(function (): void {
        Route::get('/admin/notificaciones', [NotificationAdminWebController::class, 'create'])
            ->name('notifications.admin.create');
        Route::post('/admin/notificaciones', [NotificationAdminWebController::class, 'store'])
            ->name('notifications.admin.store');
    });
});

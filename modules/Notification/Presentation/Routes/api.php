<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Notification\Presentation\Http\Controllers\NotificationStatusController;

Route::prefix('api/v1/notification')
    ->name('api.v1.notification.')
    ->group(function (): void {
        Route::get('/status', NotificationStatusController::class)
            ->name('status');
    });

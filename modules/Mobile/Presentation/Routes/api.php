<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Mobile\Presentation\Http\Controllers\MobileDeviceController;
use Modules\Mobile\Presentation\Http\Controllers\MobileSyncController;

Route::prefix('api/v1/mobile')
    ->name('api.v1.mobile.')
    ->middleware(['auth:sanctum', 'mobile.min_version'])
    ->group(function (): void {
        Route::post('/devices', [MobileDeviceController::class, 'store'])
            ->name('devices.store');

        Route::get('/devices', [MobileDeviceController::class, 'index'])
            ->name('devices.index');

        Route::delete('/devices/{deviceId}', [MobileDeviceController::class, 'destroy'])
            ->name('devices.destroy');

        Route::get('/sync', MobileSyncController::class)
            ->name('sync');
    });

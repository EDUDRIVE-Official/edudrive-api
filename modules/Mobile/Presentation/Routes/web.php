<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Mobile\Presentation\Http\Controllers\MobileDeviceWebController;

Route::middleware(['web', 'auth'])->group(function (): void {
    Route::get('/mis-dispositivos', [MobileDeviceWebController::class, 'index'])->name('mobile.devices.index');
    Route::delete('/mis-dispositivos/{deviceId}', [MobileDeviceWebController::class, 'destroy'])->name('mobile.devices.destroy');
});

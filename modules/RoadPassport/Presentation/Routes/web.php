<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\RoadPassport\Presentation\Http\Controllers\RoadPassportAdminWebController;
use Modules\RoadPassport\Presentation\Http\Controllers\RoadPassportWebController;
use Modules\RoadPassport\Presentation\Http\Controllers\RoadPassportVerificationWebController;

Route::middleware('web')->group(function (): void {
    Route::get('/verificar-pasaporte-vial', RoadPassportVerificationWebController::class)
        ->middleware('throttle:public-verification')
        ->name('road-passport.verify');
});

Route::middleware(['web', 'auth'])->group(function (): void {
    Route::get('/mi-pasaporte-vial', [RoadPassportWebController::class, 'show'])
        ->name('road-passport.show');
    Route::post('/mi-pasaporte-vial/reflexiones', [RoadPassportWebController::class, 'storeReflection'])
        ->name('road-passport.reflections.store');
    Route::post('/mi-pasaporte-vial/practicas-personales', [RoadPassportWebController::class, 'storePersonalPractice'])
        ->name('road-passport.personal-practices.store');
    Route::post('/mi-pasaporte-vial/renovar-verificacion', [RoadPassportWebController::class, 'rotateVerificationLink'])
        ->name('road-passport.verification.rotate');

    Route::middleware('permission:road_passports.view')->group(function (): void {
        Route::get('/admin/road-passport', [RoadPassportAdminWebController::class, 'search'])
            ->name('road-passport.admin.search');
    });

    Route::middleware('permission:road_passports.manage')->group(function (): void {
        Route::post('/admin/road-passport', [RoadPassportAdminWebController::class, 'issue'])
            ->name('road-passport.admin.issue');

        Route::post('/admin/road-passport/{roadPassportId}/suspend', [RoadPassportAdminWebController::class, 'suspend'])
            ->whereUuid('roadPassportId')
            ->name('road-passport.admin.suspend');

        Route::post('/admin/road-passport/{roadPassportId}/reactivate', [RoadPassportAdminWebController::class, 'reactivate'])
            ->whereUuid('roadPassportId')
            ->name('road-passport.admin.reactivate');

        Route::post('/admin/road-passport/{roadPassportId}/revoke', [RoadPassportAdminWebController::class, 'revoke'])
            ->whereUuid('roadPassportId')
            ->name('road-passport.admin.revoke');

        Route::put('/admin/road-passport/{roadPassportId}/level', [RoadPassportAdminWebController::class, 'changeLevel'])
            ->whereUuid('roadPassportId')
            ->name('road-passport.admin.level');
    });
});

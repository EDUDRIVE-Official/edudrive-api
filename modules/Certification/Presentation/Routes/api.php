<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Certification\Presentation\Http\Controllers\CertificateController;
use Modules\Certification\Presentation\Http\Controllers\CertificationStatusController;

Route::prefix('api/v1/certification')
    ->name('api.v1.certification.')
    ->group(function (): void {
        Route::get('/status', CertificationStatusController::class)
            ->name('status');

        Route::middleware('auth:sanctum')->group(function (): void {
            Route::get('/certificates/me', [CertificateController::class, 'me'])
                ->name('certificates.me');

            Route::get('/certificates/{certificateId}', [CertificateController::class, 'show'])
                ->whereUuid('certificateId')
                ->name('certificates.show');

            Route::middleware('permission:certifications.manage')->group(function (): void {
                Route::post('/certificates', [CertificateController::class, 'store'])
                    ->name('certificates.store');

                Route::post('/certificates/{certificateId}/revoke', [CertificateController::class, 'revoke'])
                    ->whereUuid('certificateId')
                    ->name('certificates.revoke');
            });
        });
    });

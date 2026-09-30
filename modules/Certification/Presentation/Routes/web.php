<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Certification\Presentation\Http\Controllers\CertificateWebController;

Route::middleware('web')->group(function (): void {
    Route::get('/verificar-certificado', [CertificateWebController::class, 'verify'])
        ->middleware('throttle:public-verification')
        ->name('certificates.verify');

    Route::middleware('auth')->group(function (): void {
        Route::get('/mis-certificados', [CertificateWebController::class, 'index'])
            ->name('certificates.index');
        Route::get('/mis-certificados/{certificateId}', [CertificateWebController::class, 'show'])
            ->whereUuid('certificateId')
            ->name('certificates.show');

        Route::middleware('permission:certifications.view')->group(function (): void {
            Route::get('/admin/certificados', [CertificateWebController::class, 'search'])
                ->name('certificates.admin.search');
        });

        Route::middleware('permission:certifications.manage')->group(function (): void {
            Route::post('/admin/certificados', [CertificateWebController::class, 'issue'])
                ->name('certificates.admin.issue');
            Route::post('/admin/certificados/{certificateId}/revocar', [CertificateWebController::class, 'revoke'])
                ->whereUuid('certificateId')
                ->name('certificates.admin.revoke');
        });
    });
});

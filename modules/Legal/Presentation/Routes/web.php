<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Legal\Presentation\Http\Controllers\LegalAdminWebController;
use Modules\Legal\Presentation\Http\Controllers\LegalConsentWebController;

Route::middleware(['web', 'auth'])->group(function (): void {
    Route::get('/mis-consentimientos', [LegalConsentWebController::class, 'index'])
        ->name('legal.consents.index');
    Route::post('/mis-consentimientos', [LegalConsentWebController::class, 'accept'])
        ->name('legal.consents.accept');
    Route::delete('/mis-consentimientos/{policyKey}', [LegalConsentWebController::class, 'revoke'])
        ->name('legal.consents.revoke');

    Route::middleware('permission:legal_policies.manage')->group(function (): void {
        Route::get('/admin/politicas-legales', [LegalAdminWebController::class, 'policies'])
            ->name('legal.admin.policies');
        Route::post('/admin/politicas-legales', [LegalAdminWebController::class, 'publish'])
            ->name('legal.admin.policies.publish');
    });

    Route::middleware('permission:organization_consents.view')->group(function (): void {
        Route::get('/admin/consentimientos-menores', [LegalAdminWebController::class, 'minors'])
            ->name('legal.admin.minors');
    });
});

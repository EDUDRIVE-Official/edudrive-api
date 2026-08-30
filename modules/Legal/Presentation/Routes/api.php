<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Legal\Presentation\Http\Controllers\ConsentController;
use Modules\Legal\Presentation\Http\Controllers\OrganizationConsentsController;
use Modules\Legal\Presentation\Http\Controllers\PolicyController;

Route::prefix('api/v1/legal')
    ->name('api.v1.legal.')
    ->group(function (): void {
        Route::get('/policies', [PolicyController::class, 'index'])
            ->name('policies.index');

        Route::middleware('auth:sanctum')->group(function (): void {
            Route::middleware('permission:legal_policies.manage')->group(function (): void {
                Route::post('/policies', [PolicyController::class, 'store'])
                    ->name('policies.store');
            });

            Route::post('/consents', [ConsentController::class, 'store'])
                ->name('consents.store');

            Route::delete('/consents/{policyKey}', [ConsentController::class, 'destroy'])
                ->name('consents.destroy');

            Route::get('/me/consents', [ConsentController::class, 'index'])
                ->name('me.consents');

            Route::middleware('permission:organization_consents.view')->group(function (): void {
                Route::get('/organizations/{organizationId}/minors-consents', [OrganizationConsentsController::class, 'index'])
                    ->whereUuid('organizationId')
                    ->name('organizations.minors-consents');
            });
        });
    });

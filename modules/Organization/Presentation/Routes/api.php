<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Organization\Presentation\Http\Controllers\OrganizationController;
use Modules\Organization\Presentation\Http\Controllers\OrganizationStatusController;

Route::prefix('api/v1/organizations')
    ->name('api.v1.organizations.')
    ->group(function (): void {
        Route::get('/status', OrganizationStatusController::class)
            ->name('status');

        Route::middleware('auth:sanctum')->group(function (): void {
            Route::get('/', [OrganizationController::class, 'index'])
                ->name('index');

            Route::get('/{organizationId}', [OrganizationController::class, 'show'])
                ->whereUuid('organizationId')
                ->name('show');

            Route::middleware('permission:organizations.manage')->group(function (): void {
                Route::post('/', [OrganizationController::class, 'store'])
                    ->name('store');

                Route::put('/{organizationId}', [OrganizationController::class, 'update'])
                    ->whereUuid('organizationId')
                    ->name('update');

                Route::post('/{organizationId}/campuses', [OrganizationController::class, 'addCampus'])
                    ->whereUuid('organizationId')
                    ->name('campuses.store');
            });
        });
    });

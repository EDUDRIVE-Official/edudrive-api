<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Authorization\Presentation\Http\Controllers\AuthorizationStatusController;
use Modules\Authorization\Presentation\Http\Controllers\MyRolesController;
use Modules\Authorization\Presentation\Http\Controllers\RoleAssignmentController;

Route::prefix('api/v1/authorization')
    ->name('api.v1.authorization.')
    ->group(function (): void {
        Route::get('/status', AuthorizationStatusController::class)
            ->name('status');

        Route::middleware('auth:sanctum')->group(function (): void {
            Route::get('/me/roles', MyRolesController::class)
                ->name('me.roles');

            Route::post('/role-assignments', [RoleAssignmentController::class, 'store'])
                ->middleware('permission:roles.manage')
                ->name('role-assignments.store');
        });
    });

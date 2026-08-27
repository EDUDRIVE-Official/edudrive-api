<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Admin\Presentation\Http\Controllers\AdminStatusController;
use Modules\Admin\Presentation\Http\Controllers\ReportController;
use Modules\Admin\Presentation\Http\Controllers\SystemOperationController;
use Modules\Admin\Presentation\Http\Controllers\SystemSettingController;

Route::prefix('api/v1/admin')
    ->name('api.v1.admin.')
    ->group(function (): void {
        Route::get('/status', AdminStatusController::class)
            ->name('status');

        Route::middleware('auth:sanctum')->group(function (): void {
            Route::middleware('permission:system_settings.view')->group(function (): void {
                Route::get('/settings', [SystemSettingController::class, 'index'])
                    ->name('settings.index');

                Route::get('/settings/{key}', [SystemSettingController::class, 'show'])
                    ->name('settings.show');
            });

            Route::middleware('permission:system_settings.manage')->group(function (): void {
                Route::put('/settings/{key}', [SystemSettingController::class, 'update'])
                    ->name('settings.update');
            });

            Route::middleware('permission:reports.view')->group(function (): void {
                Route::get('/reports/summary', [ReportController::class, 'summary'])
                    ->name('reports.summary');
            });

            Route::middleware('permission:system_operations.view')->group(function (): void {
                Route::get('/operations/health', [SystemOperationController::class, 'health'])
                    ->name('operations.health');

                Route::get('/operations/audit-logs', [SystemOperationController::class, 'auditLogs'])
                    ->name('operations.audit-logs');
            });
        });
    });

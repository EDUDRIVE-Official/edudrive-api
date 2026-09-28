<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Admin\Presentation\Http\Controllers\AdminWebController;

Route::middleware(['web', 'auth'])->prefix('admin/sistema')->name('admin.system.')->group(function (): void {
    Route::middleware('permission:reports.view')->group(function (): void {
        Route::get('/', [AdminWebController::class, 'summary'])->name('summary');
    });

    Route::middleware('permission:system_operations.view')->group(function (): void {
        Route::get('/operaciones', [AdminWebController::class, 'operations'])->name('operations');
    });

    Route::middleware('permission:exports.view')->group(function (): void {
        Route::post('/auditoria/exportar', [AdminWebController::class, 'exportAuditLogs'])->name('audit.export');
    });

    Route::middleware('permission:system_settings.view')->group(function (): void {
        Route::get('/configuracion', [AdminWebController::class, 'settings'])->name('settings');
    });

    Route::middleware('permission:system_settings.manage')->group(function (): void {
        Route::put('/configuracion/{key}', [AdminWebController::class, 'updateSetting'])->name('settings.update');
    });
});

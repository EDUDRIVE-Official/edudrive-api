<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\FileStorage\Presentation\Http\Controllers\FileController;
use Modules\FileStorage\Presentation\Http\Controllers\FileStorageStatusController;

Route::prefix('api/v1/files')
    ->name('api.v1.files.')
    ->group(function (): void {
        Route::get('/status', FileStorageStatusController::class)
            ->name('status');

        Route::middleware('auth:sanctum')->group(function (): void {
            Route::post('/', [FileController::class, 'store'])
                ->name('store');

            Route::get('/me', [FileController::class, 'mine'])
                ->name('mine');

            Route::get('/{fileId}', [FileController::class, 'show'])
                ->whereUuid('fileId')
                ->name('show');

            Route::get('/{fileId}/download-url', [FileController::class, 'downloadUrl'])
                ->whereUuid('fileId')
                ->name('download-url');

            Route::delete('/{fileId}', [FileController::class, 'destroy'])
                ->whereUuid('fileId')
                ->name('destroy');

            Route::middleware('permission:files.manage')->group(function (): void {
                Route::put('/{fileId}/scan-status', [FileController::class, 'setScanStatus'])
                    ->whereUuid('fileId')
                    ->name('scan-status.update');
            });
        });
    });

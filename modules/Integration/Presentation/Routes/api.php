<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Integration\Presentation\Http\Controllers\ApiConsumerController;
use Modules\Integration\Presentation\Http\Controllers\ExternalReportsPingController;
use Modules\Integration\Presentation\Http\Controllers\ExternalStatusController;

Route::prefix('api/v1/external')
    ->name('api.v1.external.')
    ->middleware(['api_consumer.auth', 'throttle:external-integration'])
    ->group(function (): void {
        Route::get('/status', ExternalStatusController::class)
            ->name('status');

        Route::get('/reports/ping', ExternalReportsPingController::class)
            ->middleware('scope:reports.view')
            ->name('reports.ping');
    });

Route::prefix('api/v1/integration')
    ->name('api.v1.integration.')
    ->middleware('auth:sanctum')
    ->group(function (): void {
        Route::middleware('permission:api_consumers.view')->group(function (): void {
            Route::get('/api-consumers', [ApiConsumerController::class, 'index'])
                ->name('api-consumers.index');

            Route::get('/api-consumers/{consumerId}', [ApiConsumerController::class, 'show'])
                ->whereUuid('consumerId')
                ->name('api-consumers.show');
        });

        Route::middleware('permission:api_consumers.manage')->group(function (): void {
            Route::post('/api-consumers', [ApiConsumerController::class, 'store'])
                ->name('api-consumers.store');

            Route::post('/api-consumers/{consumerId}/suspend', [ApiConsumerController::class, 'suspend'])
                ->whereUuid('consumerId')
                ->name('api-consumers.suspend');

            Route::post('/api-consumers/{consumerId}/reactivate', [ApiConsumerController::class, 'reactivate'])
                ->whereUuid('consumerId')
                ->name('api-consumers.reactivate');

            Route::post('/api-consumers/{consumerId}/revoke', [ApiConsumerController::class, 'revoke'])
                ->whereUuid('consumerId')
                ->name('api-consumers.revoke');

            Route::post('/api-consumers/{consumerId}/rotate-key', [ApiConsumerController::class, 'rotateKey'])
                ->whereUuid('consumerId')
                ->name('api-consumers.rotate-key');
        });
    });

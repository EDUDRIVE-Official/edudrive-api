<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Webhook\Presentation\Http\Controllers\WebhookWebController;

Route::middleware(['web', 'auth'])->prefix('admin/webhooks')->name('webhooks.')->group(function (): void {
    Route::middleware('permission:webhooks.view')->group(function (): void {
        Route::get('/', [WebhookWebController::class, 'index'])->name('index');
        Route::get('/{subscriptionId}/entregas', [WebhookWebController::class, 'deliveries'])->whereUuid('subscriptionId')->name('deliveries');
    });

    Route::middleware('permission:webhooks.manage')->group(function (): void {
        Route::post('/', [WebhookWebController::class, 'store'])->name('store');
        Route::post('/{subscriptionId}/suspender', [WebhookWebController::class, 'suspend'])->whereUuid('subscriptionId')->name('suspend');
        Route::post('/{subscriptionId}/reactivar', [WebhookWebController::class, 'reactivate'])->whereUuid('subscriptionId')->name('reactivate');
        Route::post('/{subscriptionId}/rotar-secreto', [WebhookWebController::class, 'rotateSecret'])->whereUuid('subscriptionId')->name('rotate-secret');
        Route::post('/entregas/{deliveryId}/reintentar', [WebhookWebController::class, 'retry'])->whereUuid('deliveryId')->name('retry');
    });
});

<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Webhook\Presentation\Http\Controllers\WebhookDeliveryController;
use Modules\Webhook\Presentation\Http\Controllers\WebhookSubscriptionController;

Route::prefix('api/v1/webhooks')
    ->name('api.v1.webhooks.')
    ->middleware('auth:sanctum')
    ->group(function (): void {
        Route::middleware('permission:webhooks.view')->group(function (): void {
            Route::get('/subscriptions', [WebhookSubscriptionController::class, 'index'])
                ->name('subscriptions.index');

            Route::get('/subscriptions/{subscriptionId}', [WebhookSubscriptionController::class, 'show'])
                ->whereUuid('subscriptionId')
                ->name('subscriptions.show');

            Route::get('/subscriptions/{subscriptionId}/deliveries', [WebhookDeliveryController::class, 'index'])
                ->whereUuid('subscriptionId')
                ->name('subscriptions.deliveries.index');
        });

        Route::middleware('permission:webhooks.manage')->group(function (): void {
            Route::post('/subscriptions', [WebhookSubscriptionController::class, 'store'])
                ->name('subscriptions.store');

            Route::post('/subscriptions/{subscriptionId}/suspend', [WebhookSubscriptionController::class, 'suspend'])
                ->whereUuid('subscriptionId')
                ->name('subscriptions.suspend');

            Route::post('/subscriptions/{subscriptionId}/reactivate', [WebhookSubscriptionController::class, 'reactivate'])
                ->whereUuid('subscriptionId')
                ->name('subscriptions.reactivate');

            Route::post('/subscriptions/{subscriptionId}/rotate-secret', [WebhookSubscriptionController::class, 'rotateSecret'])
                ->whereUuid('subscriptionId')
                ->name('subscriptions.rotate-secret');

            Route::post('/deliveries/{deliveryId}/retry', [WebhookDeliveryController::class, 'retry'])
                ->whereUuid('deliveryId')
                ->name('deliveries.retry');
        });
    });

<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Integration\Presentation\Http\Controllers\ApiConsumerWebController;

Route::middleware(['web', 'auth'])->prefix('admin/integraciones')->name('integrations.')->group(function (): void {
    Route::get('/', [ApiConsumerWebController::class, 'index'])
        ->middleware('permission:api_consumers.view')
        ->name('index');

    Route::middleware('permission:api_consumers.manage')->group(function (): void {
        Route::post('/', [ApiConsumerWebController::class, 'store'])->name('store');
        Route::post('/{consumerId}/suspender', [ApiConsumerWebController::class, 'suspend'])->whereUuid('consumerId')->name('suspend');
        Route::post('/{consumerId}/reactivar', [ApiConsumerWebController::class, 'reactivate'])->whereUuid('consumerId')->name('reactivate');
        Route::post('/{consumerId}/revocar', [ApiConsumerWebController::class, 'revoke'])->whereUuid('consumerId')->name('revoke');
        Route::post('/{consumerId}/rotar-llave', [ApiConsumerWebController::class, 'rotateKey'])->whereUuid('consumerId')->name('rotate-key');
    });
});

<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\AsyncProcessing\Presentation\Http\Controllers\AsyncJobController;

Route::prefix('api/v1/async-jobs')
    ->name('api.v1.async-jobs.')
    ->middleware('auth:sanctum')
    ->group(function (): void {
        Route::get('/{asyncJobId}', [AsyncJobController::class, 'show'])
            ->whereUuid('asyncJobId')
            ->name('show');
    });

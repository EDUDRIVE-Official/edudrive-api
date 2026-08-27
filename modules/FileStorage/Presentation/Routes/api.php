<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\FileStorage\Presentation\Http\Controllers\FileStorageStatusController;

Route::prefix('api/v1/files')
    ->name('api.v1.files.')
    ->group(function (): void {
        Route::get('/status', FileStorageStatusController::class)
            ->name('status');
    });

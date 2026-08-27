<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Admin\Presentation\Http\Controllers\AdminStatusController;

Route::prefix('api/v1/admin')
    ->name('api.v1.admin.')
    ->group(function (): void {
        Route::get('/status', AdminStatusController::class)
            ->name('status');
    });

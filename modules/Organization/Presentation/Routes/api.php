<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Organization\Presentation\Http\Controllers\OrganizationStatusController;

Route::prefix('api/v1/organizations')
    ->name('api.v1.organizations.')
    ->group(function (): void {
        Route::get('/status', OrganizationStatusController::class)
            ->name('status');
    });

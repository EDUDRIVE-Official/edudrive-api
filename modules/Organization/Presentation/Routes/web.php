<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Organization\Presentation\Http\Controllers\OrganizationWebController;

Route::middleware(['web', 'auth'])->group(function (): void {
    Route::middleware('permission:organizations.view')->group(function (): void {
        Route::get('/organizations', [OrganizationWebController::class, 'index'])
            ->name('organizations.index');
    });

    Route::middleware('permission:organizations.manage')->group(function (): void {
        Route::get('/organizations/create', [OrganizationWebController::class, 'create'])
            ->name('organizations.create');

        Route::post('/organizations', [OrganizationWebController::class, 'store'])
            ->name('organizations.store');
    });
});

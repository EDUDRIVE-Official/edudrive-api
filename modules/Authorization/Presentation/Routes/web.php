<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Authorization\Presentation\Http\Controllers\RoleAssignmentWebController;

Route::middleware(['web', 'auth', 'permission:roles.manage'])->group(function (): void {
    Route::get('/roles/assign', [RoleAssignmentWebController::class, 'create'])
        ->name('roles.assign');

    Route::post('/roles/assign', [RoleAssignmentWebController::class, 'store'])
        ->name('roles.assign.store');
});

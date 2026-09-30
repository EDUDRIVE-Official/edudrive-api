<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Identity\Presentation\Http\Controllers\GuardianWebController;
use Modules\Identity\Presentation\Http\Controllers\LoginWebController;
use Modules\Identity\Presentation\Http\Controllers\LogoutWebController;
use Modules\Identity\Presentation\Http\Controllers\OrganizationMembershipWebController;
use Modules\Identity\Presentation\Http\Controllers\PasswordResetWebController;
use Modules\Identity\Presentation\Http\Controllers\StudentProfileWebController;
use Modules\Identity\Presentation\Http\Controllers\UserWebController;

Route::middleware('web')->group(function (): void {
    Route::middleware('guest')->group(function (): void {
        Route::get('/login', [LoginWebController::class, 'create'])->name('login');
        Route::post('/login', [LoginWebController::class, 'store'])->middleware('throttle:login')->name('login.attempt');
        Route::get('/recuperar-acceso', [PasswordResetWebController::class, 'createRequest'])->name('password.request');
        Route::post('/recuperar-acceso', [PasswordResetWebController::class, 'sendLink'])->middleware('throttle:forgot-password')->name('password.email');
        Route::get('/restablecer-contrasena/{token}', [PasswordResetWebController::class, 'createReset'])->name('password.reset');
        Route::post('/restablecer-contrasena', [PasswordResetWebController::class, 'reset'])->middleware('throttle:reset-password')->name('password.update');
    });

    Route::middleware('auth')->group(function (): void {
        Route::post('/logout', LogoutWebController::class)->name('logout');

        Route::get('/mi-perfil', [StudentProfileWebController::class, 'show'])->name('student-profile.show');
        Route::put('/mi-perfil', [StudentProfileWebController::class, 'update'])->name('student-profile.update');
        Route::post('/mi-perfil/organizaciones', [OrganizationMembershipWebController::class, 'store'])->name('student-profile.organizations.request');
        Route::delete('/mi-perfil/organizaciones/{membershipRequestId}', [OrganizationMembershipWebController::class, 'cancel'])->whereUuid('membershipRequestId')->name('student-profile.organizations.cancel');
        Route::get('/mi-acompanamiento', [GuardianWebController::class, 'index'])->name('guardians.web.index');
        Route::get('/mi-acompanamiento/{minorUserId}', [GuardianWebController::class, 'show'])
            ->whereUuid('minorUserId')->name('guardians.web.show');
        Route::post('/mi-acompanamiento/{minorUserId}/observaciones', [GuardianWebController::class, 'storeObservation'])
            ->whereUuid('minorUserId')->name('guardians.web.observations.store');

        Route::middleware('permission:users.view')->group(function (): void {
            Route::get('/users', [UserWebController::class, 'index'])->name('users.index');
            Route::get('/users/{userId}', [UserWebController::class, 'show'])->whereUuid('userId')->name('users.show');
        });

        Route::middleware('permission:users.manage')->group(function (): void {
            Route::get('/users/create', [UserWebController::class, 'create'])->name('users.create');
            Route::post('/users', [UserWebController::class, 'store'])->name('users.store');
            Route::get('/users/{userId}/edit', [UserWebController::class, 'edit'])->whereUuid('userId')->name('users.edit');
            Route::put('/users/{userId}', [UserWebController::class, 'update'])->whereUuid('userId')->name('users.update');
            Route::post('/users/{userId}/activate', [UserWebController::class, 'activate'])
                ->whereUuid('userId')
                ->name('users.activate');

            Route::post('/users/{userId}/deactivate', [UserWebController::class, 'deactivate'])
                ->whereUuid('userId')
                ->name('users.deactivate');
            Route::post('/users/{userId}/temporary-password', [UserWebController::class, 'resetTemporaryPassword'])
                ->whereUuid('userId')->name('users.temporary-password');
            Route::delete('/users/{userId}/anonymize', [UserWebController::class, 'anonymize'])
                ->whereUuid('userId')->name('users.anonymize');
        });

        Route::middleware('permission:users.manage')->group(function (): void {
            Route::post('/organizaciones/solicitudes/{membershipRequestId}/aprobar', [OrganizationMembershipWebController::class, 'approve'])->whereUuid('membershipRequestId')->name('organizations.memberships.approve');
            Route::post('/organizaciones/solicitudes/{membershipRequestId}/rechazar', [OrganizationMembershipWebController::class, 'reject'])->whereUuid('membershipRequestId')->name('organizations.memberships.reject');
        });

        Route::delete('/users/{userId}/roles/{assignmentId}', [UserWebController::class, 'revokeRole'])
            ->middleware('permission:roles.manage')->whereUuid(['userId', 'assignmentId'])->name('users.roles.destroy');
        Route::post('/users/{userId}/reset-learning', [UserWebController::class, 'resetLearning'])
            ->middleware('permission:roles.manage')->whereUuid('userId')->name('users.learning.reset');

        Route::middleware('permission:guardian_relationships.manage')->group(function (): void {
            Route::post('/users/guardian-relationships', [UserWebController::class, 'linkGuardian'])->name('users.guardians.store');
            Route::delete('/users/guardian-relationships/{relationshipId}', [UserWebController::class, 'revokeGuardian'])
                ->whereUuid('relationshipId')->name('users.guardians.destroy');
        });
    });
});

<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Identity\Presentation\Http\Controllers\ActivateUserController;
use Modules\Identity\Presentation\Http\Controllers\AuthController;
use Modules\Identity\Presentation\Http\Controllers\BulkImportUsersController;
use Modules\Identity\Presentation\Http\Controllers\DeactivateUserController;
use Modules\Identity\Presentation\Http\Controllers\DeleteAccountController;
use Modules\Identity\Presentation\Http\Controllers\ExportMyDataController;
use Modules\Identity\Presentation\Http\Controllers\ForgotPasswordController;
use Modules\Identity\Presentation\Http\Controllers\GetMyStudentProfileController;
use Modules\Identity\Presentation\Http\Controllers\ListUsersController;
use Modules\Identity\Presentation\Http\Controllers\LoginController;
use Modules\Identity\Presentation\Http\Controllers\LogoutAllController;
use Modules\Identity\Presentation\Http\Controllers\LogoutController;
use Modules\Identity\Presentation\Http\Controllers\MeController;
use Modules\Identity\Presentation\Http\Controllers\ResendEmailVerificationController;
use Modules\Identity\Presentation\Http\Controllers\ResetPasswordController;
use Modules\Identity\Presentation\Http\Controllers\RevokeSessionController;
use Modules\Identity\Presentation\Http\Controllers\SessionsController;
use Modules\Identity\Presentation\Http\Controllers\ShowUserController;
use Modules\Identity\Presentation\Http\Controllers\UpdateStudentProfileController;
use Modules\Identity\Presentation\Http\Controllers\VerifyEmailController;

Route::middleware('api')
    ->prefix('api/v1/auth')
    ->group(function (): void {
        Route::post('/register', [AuthController::class, 'register'])
            ->middleware('throttle:register')
            ->name('api.v1.auth.register');

        Route::post('/login', LoginController::class)
            ->middleware('throttle:login')
            ->name('api.v1.auth.login');

        Route::post('/verify-email', VerifyEmailController::class)
            ->middleware('throttle:verify-email')
            ->name('api.v1.auth.verify-email');

        Route::post('/resend-verification', ResendEmailVerificationController::class)
            ->middleware('throttle:resend-verification')
            ->name('api.v1.auth.resend-verification');

        Route::post('/forgot-password', ForgotPasswordController::class)
            ->middleware('throttle:forgot-password')
            ->name('api.v1.auth.forgot-password');

        Route::post('/reset-password', ResetPasswordController::class)
            ->middleware('throttle:reset-password')
            ->name('api.v1.auth.reset-password');

        Route::middleware('auth:sanctum')->group(function (): void {
            Route::get('/me', MeController::class)
                ->name('api.v1.auth.me');

            Route::delete('/me', DeleteAccountController::class)
                ->name('api.v1.auth.me.delete');

            Route::get('/me/data-export', ExportMyDataController::class)
                ->name('api.v1.auth.me.data-export');

            Route::get('/me/profile', GetMyStudentProfileController::class)
                ->name('api.v1.auth.me.profile.show');

            Route::put('/me/profile', UpdateStudentProfileController::class)
                ->name('api.v1.auth.me.profile.update');

            Route::get('/sessions', SessionsController::class)
                ->name('api.v1.auth.sessions');

            Route::delete('/sessions/{tokenId}', RevokeSessionController::class)
                ->whereNumber('tokenId')
                ->name('api.v1.auth.sessions.revoke');

            Route::post('/logout', LogoutController::class)
                ->name('api.v1.auth.logout');

            Route::post('/logout-all', LogoutAllController::class)
                ->name('api.v1.auth.logout-all');
        });
    });

Route::middleware(['api', 'auth:sanctum'])
    ->prefix('api/v1/users')
    ->group(function (): void {
        Route::middleware('permission:users.view')->group(function (): void {
            Route::get('/', ListUsersController::class)
                ->name('api.v1.users.index');

            Route::get('/{userId}', ShowUserController::class)
                ->whereUuid('userId')
                ->name('api.v1.users.show');
        });

        Route::middleware('permission:users.manage')->group(function (): void {
            Route::post('/{userId}/activate', ActivateUserController::class)
                ->whereUuid('userId')
                ->name('api.v1.users.activate');

            Route::post('/{userId}/deactivate', DeactivateUserController::class)
                ->whereUuid('userId')
                ->name('api.v1.users.deactivate');

            Route::post('/import', BulkImportUsersController::class)
                ->name('api.v1.users.import');
        });
    });

<?php

declare(strict_types=1);

namespace Modules\Identity\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Identity\Application\Services\AccessTokenIssuer;
use Modules\Identity\Application\Services\AccessTokenRevoker;
use Modules\Identity\Application\Services\PasswordHasher;
use Modules\Identity\Application\Services\SessionRepository;
use Modules\Identity\Application\Services\UuidGenerator;
use Modules\Identity\Domain\Repositories\EmailVerificationTokenRepository;
use Modules\Identity\Domain\Repositories\GuardianRelationshipRepository;
use Modules\Identity\Domain\Repositories\PasswordResetTokenRepository;
use Modules\Identity\Domain\Repositories\StudentProfileRepository;
use Modules\Identity\Domain\Repositories\TeacherProfileRepository;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Infrastructure\Persistence\Repositories\EloquentEmailVerificationTokenRepository;
use Modules\Identity\Infrastructure\Persistence\Repositories\EloquentGuardianRelationshipRepository;
use Modules\Identity\Infrastructure\Persistence\Repositories\EloquentPasswordResetTokenRepository;
use Modules\Identity\Infrastructure\Persistence\Repositories\EloquentStudentProfileRepository;
use Modules\Identity\Infrastructure\Persistence\Repositories\EloquentTeacherProfileRepository;
use Modules\Identity\Infrastructure\Persistence\Repositories\EloquentUserRepository;
use Modules\Identity\Infrastructure\Security\LaravelPasswordHasher;
use Modules\Identity\Infrastructure\Security\SanctumAccessTokenIssuer;
use Modules\Identity\Infrastructure\Security\SanctumAccessTokenRevoker;
use Modules\Identity\Infrastructure\Security\SanctumSessionRepository;
use Modules\Identity\Infrastructure\Support\LaravelUuidGenerator;
use Modules\Identity\Presentation\Console\PurgeInactiveAccountsCommand;

final class IdentityServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            UserRepository::class,
            EloquentUserRepository::class,
        );

        $this->app->bind(
            PasswordHasher::class,
            LaravelPasswordHasher::class,
        );

        $this->app->bind(
            UuidGenerator::class,
            LaravelUuidGenerator::class,
        );

        $this->app->bind(
            AccessTokenIssuer::class,
            SanctumAccessTokenIssuer::class,
        );

        $this->app->bind(
            AccessTokenRevoker::class,
            SanctumAccessTokenRevoker::class,
        );

        $this->app->bind(
            SessionRepository::class,
            SanctumSessionRepository::class,
        );

        $this->app->bind(
            PasswordResetTokenRepository::class,
            EloquentPasswordResetTokenRepository::class,
        );

        $this->app->bind(
            EmailVerificationTokenRepository::class,
            EloquentEmailVerificationTokenRepository::class,
        );

        $this->app->bind(
            StudentProfileRepository::class,
            EloquentStudentProfileRepository::class,
        );

        $this->app->bind(
            TeacherProfileRepository::class,
            EloquentTeacherProfileRepository::class,
        );

        $this->app->bind(
            GuardianRelationshipRepository::class,
            EloquentGuardianRelationshipRepository::class,
        );
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(
            __DIR__.'/../Infrastructure/Persistence/Migrations',
        );

        $this->loadRoutesFrom(
            __DIR__.'/../routes/api.php',
        );

        $this->loadRoutesFrom(
            __DIR__.'/../routes/web.php',
        );

        $this->commands([
            PurgeInactiveAccountsCommand::class,
        ]);
    }
}

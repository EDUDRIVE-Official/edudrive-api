<?php

declare(strict_types=1);

namespace Modules\Identity\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Identity\Application\Services\AccessTokenIssuer;
use Modules\Identity\Application\Services\PasswordHasher;
use Modules\Identity\Application\Services\UuidGenerator;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Infrastructure\Persistence\Repositories\EloquentUserRepository;
use Modules\Identity\Infrastructure\Security\LaravelPasswordHasher;
use Modules\Identity\Infrastructure\Security\SanctumAccessTokenIssuer;
use Modules\Identity\Infrastructure\Support\LaravelUuidGenerator;

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
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(
            __DIR__.'/../Infrastructure/Persistence/Migrations',
        );

        $this->loadRoutesFrom(
            __DIR__.'/../routes/api.php',
        );
    }
}

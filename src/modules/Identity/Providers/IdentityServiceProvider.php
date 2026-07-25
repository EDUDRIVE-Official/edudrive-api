<?php

declare(strict_types=1);

namespace Modules\Identity\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Infrastructure\Persistence\Repositories\EloquentUserRepository;

final class IdentityServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            UserRepository::class,
            EloquentUserRepository::class,
        );
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(
            __DIR__.'/../Infrastructure/Persistence/Migrations',
        );
    }
}

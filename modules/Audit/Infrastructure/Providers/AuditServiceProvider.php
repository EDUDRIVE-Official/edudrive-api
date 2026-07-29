<?php

declare(strict_types=1);

namespace Modules\Audit\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Audit\Application\Contracts\AuditRepository;
use Modules\Audit\Application\Services\AuditLogger;
use Modules\Audit\Infrastructure\Persistence\Eloquent\Repositories\EloquentAuditRepository;
use Modules\Audit\Infrastructure\Services\DatabaseAuditLogger;

final class AuditServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            AuditRepository::class,
            EloquentAuditRepository::class,
        );

        $this->app->bind(
            AuditLogger::class,
            DatabaseAuditLogger::class,
        );
    }

    public function boot(): void
    {
        //
    }
}

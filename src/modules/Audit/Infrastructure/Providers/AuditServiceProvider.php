<?php

declare(strict_types=1);

namespace Modules\Audit\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Audit\Application\Services\AuditLogger;
use Modules\Audit\Infrastructure\Services\DatabaseAuditLogger;

final class AuditServiceProvider extends ServiceProvider
{
    public function register(): void
    {
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

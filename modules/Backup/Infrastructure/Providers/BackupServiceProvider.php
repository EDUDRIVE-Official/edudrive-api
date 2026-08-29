<?php

declare(strict_types=1);

namespace Modules\Backup\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Backup\Application\Services\DatabaseDumper;
use Modules\Backup\Application\Services\DatabaseRestorer;
use Modules\Backup\Infrastructure\Services\PgDumpDatabaseDumper;
use Modules\Backup\Infrastructure\Services\PgRestoreDatabaseRestorer;
use Modules\Backup\Presentation\Console\BackupDatabaseCommand;
use Modules\Backup\Presentation\Console\RestoreDatabaseCommand;

final class BackupServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(DatabaseDumper::class, PgDumpDatabaseDumper::class);
        $this->app->bind(DatabaseRestorer::class, PgRestoreDatabaseRestorer::class);
    }

    public function boot(): void
    {
        $this->commands([
            BackupDatabaseCommand::class,
            RestoreDatabaseCommand::class,
        ]);
    }
}

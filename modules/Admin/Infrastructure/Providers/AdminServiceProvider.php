<?php

declare(strict_types=1);

namespace Modules\Admin\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Admin\Application\Commands\ExportAuditLogsCommand;
use Modules\Admin\Application\Commands\SetSystemSettingCommand;
use Modules\Admin\Application\Queries\GetAuditLogsQuery;
use Modules\Admin\Application\Queries\GetSystemHealthQuery;
use Modules\Admin\Application\Queries\GetSystemSettingQuery;
use Modules\Admin\Application\Queries\GetSystemSummaryQuery;
use Modules\Admin\Application\Queries\ListSystemSettingsQuery;
use Modules\Admin\Application\UseCases\ExportAuditLogsHandler;
use Modules\Admin\Application\UseCases\GetAuditLogsHandler;
use Modules\Admin\Application\UseCases\GetSystemHealthHandler;
use Modules\Admin\Application\UseCases\GetSystemSettingHandler;
use Modules\Admin\Application\UseCases\GetSystemSummaryHandler;
use Modules\Admin\Application\UseCases\ListSystemSettingsHandler;
use Modules\Admin\Application\UseCases\SetSystemSettingHandler;
use Modules\Admin\Domain\Repositories\SystemSettingRepository;
use Modules\Admin\Domain\Repositories\SystemSummaryRepository;
use Modules\Admin\Infrastructure\Persistence\Eloquent\Repositories\EloquentSystemSettingRepository;
use Modules\Admin\Infrastructure\Persistence\Eloquent\Repositories\EloquentSystemSummaryRepository;
use Modules\Foundation\Application\Bus\MessageHandlerRegistry;

final class AdminServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(SystemSettingRepository::class, EloquentSystemSettingRepository::class);
        $this->app->bind(SystemSummaryRepository::class, EloquentSystemSummaryRepository::class);
    }

    public function boot(MessageHandlerRegistry $registry): void
    {
        $registry->register(SetSystemSettingCommand::class, SetSystemSettingHandler::class);
        $registry->register(GetSystemSettingQuery::class, GetSystemSettingHandler::class);
        $registry->register(ListSystemSettingsQuery::class, ListSystemSettingsHandler::class);

        $registry->register(GetSystemSummaryQuery::class, GetSystemSummaryHandler::class);

        $registry->register(GetSystemHealthQuery::class, GetSystemHealthHandler::class);
        $registry->register(GetAuditLogsQuery::class, GetAuditLogsHandler::class);
        $registry->register(ExportAuditLogsCommand::class, ExportAuditLogsHandler::class);

        $this->loadRoutesFrom(
            dirname(__DIR__, 2).'/Presentation/Routes/api.php',
        );

        $this->loadMigrationsFrom(
            dirname(__DIR__).'/Persistence/Migrations',
        );
    }
}

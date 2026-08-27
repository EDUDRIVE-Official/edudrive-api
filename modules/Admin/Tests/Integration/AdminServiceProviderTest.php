<?php

declare(strict_types=1);

use Modules\Admin\Application\Commands\SetSystemSettingCommand;
use Modules\Admin\Application\Queries\GetAuditLogsQuery;
use Modules\Admin\Application\Queries\GetSystemHealthQuery;
use Modules\Admin\Application\Queries\GetSystemSettingQuery;
use Modules\Admin\Application\Queries\GetSystemSummaryQuery;
use Modules\Admin\Application\Queries\ListSystemSettingsQuery;
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

it('registra los repositorios de administracion en el contenedor', function (): void {
    expect(app(SystemSettingRepository::class))->toBeInstanceOf(EloquentSystemSettingRepository::class)
        ->and(app(SystemSummaryRepository::class))->toBeInstanceOf(EloquentSystemSummaryRepository::class);
});

it('registra los handlers CQRS de administracion en el registry', function (): void {
    $registry = app(MessageHandlerRegistry::class);

    expect($registry->handlerFor(SetSystemSettingCommand::class))->toBe(SetSystemSettingHandler::class)
        ->and($registry->handlerFor(GetSystemSettingQuery::class))->toBe(GetSystemSettingHandler::class)
        ->and($registry->handlerFor(ListSystemSettingsQuery::class))->toBe(ListSystemSettingsHandler::class)
        ->and($registry->handlerFor(GetSystemSummaryQuery::class))->toBe(GetSystemSummaryHandler::class)
        ->and($registry->handlerFor(GetSystemHealthQuery::class))->toBe(GetSystemHealthHandler::class)
        ->and($registry->handlerFor(GetAuditLogsQuery::class))->toBe(GetAuditLogsHandler::class);
});

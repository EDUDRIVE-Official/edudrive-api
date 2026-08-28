<?php

declare(strict_types=1);

namespace Modules\Admin\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\Admin\Application\Commands\ExportAuditLogsCommand;
use Modules\Admin\Application\Queries\GetAuditLogsQuery;
use Modules\Admin\Application\Queries\GetSystemHealthQuery;
use Modules\Admin\Application\Responses\AuditLogResponse;
use Modules\Admin\Application\Responses\SystemHealthResponse;
use Modules\Foundation\Application\Bus\CommandBus;
use Modules\Foundation\Application\Bus\QueryBus;
use Modules\Foundation\Application\Responses\ExportResponse;

final class SystemOperationController
{
    public function health(QueryBus $queryBus): JsonResponse
    {
        $result = $queryBus->ask(new GetSystemHealthQuery);
        assert($result instanceof SystemHealthResponse);

        return response()->json(['data' => $result->toArray()]);
    }

    public function auditLogs(QueryBus $queryBus): JsonResponse
    {
        $result = $queryBus->ask(new GetAuditLogsQuery);
        assert(is_array($result));

        return response()->json(['data' => array_map(
            static fn (AuditLogResponse $log): array => $log->toArray(),
            $result,
        )]);
    }

    public function exportAuditLogs(CommandBus $commandBus): JsonResponse
    {
        $result = $commandBus->dispatch(new ExportAuditLogsCommand);
        assert($result instanceof ExportResponse);

        return response()->json(['data' => $result->toArray()]);
    }
}

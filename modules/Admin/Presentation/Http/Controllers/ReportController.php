<?php

declare(strict_types=1);

namespace Modules\Admin\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\Admin\Application\Queries\GetSystemSummaryQuery;
use Modules\Admin\Application\Responses\SystemSummaryResponse;
use Modules\Foundation\Application\Bus\QueryBus;

final class ReportController
{
    public function summary(QueryBus $queryBus): JsonResponse
    {
        $result = $queryBus->ask(new GetSystemSummaryQuery);
        assert($result instanceof SystemSummaryResponse);

        return response()->json(['data' => $result->toArray()]);
    }
}

<?php

declare(strict_types=1);

namespace Modules\AsyncProcessing\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\AsyncProcessing\Application\Queries\GetAsyncJobQuery;
use Modules\AsyncProcessing\Application\Responses\AsyncJobResponse;
use Modules\Foundation\Application\Bus\QueryBus;

final class AsyncJobController
{
    public function show(string $asyncJobId, Request $request, QueryBus $queryBus): JsonResponse
    {
        $result = $queryBus->ask(new GetAsyncJobQuery(
            asyncJobId: $asyncJobId,
            requestedByUserId: (string) $request->user()?->getAuthIdentifier(),
        ));
        assert($result instanceof AsyncJobResponse);

        return response()->json(['data' => $result->toArray()]);
    }
}

<?php

declare(strict_types=1);

namespace Modules\Mobile\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Foundation\Application\Bus\QueryBus;
use Modules\Mobile\Application\Queries\GetMobileSyncQuery;
use Modules\Mobile\Application\Responses\MobileSyncResponse;

final class MobileSyncController
{
    public function __invoke(Request $request, QueryBus $queryBus): JsonResponse
    {
        $result = $queryBus->ask(new GetMobileSyncQuery(
            userId: (string) $request->user()?->getAuthIdentifier(),
            since: $request->query('since') === null ? null : (string) $request->query('since'),
        ));
        assert($result instanceof MobileSyncResponse);

        return response()->json(['data' => $result->toArray()]);
    }
}

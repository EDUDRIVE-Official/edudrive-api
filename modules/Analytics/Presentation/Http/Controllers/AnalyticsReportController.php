<?php

declare(strict_types=1);

namespace Modules\Analytics\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\Analytics\Application\Commands\RequestAnalyticsReportCommand;
use Modules\Analytics\Presentation\Http\Requests\RequestAnalyticsReportRequest;
use Modules\AsyncProcessing\Application\Responses\AsyncJobResponse;
use Modules\Foundation\Application\Bus\CommandBus;
use Symfony\Component\HttpFoundation\Response;

final class AnalyticsReportController
{
    public function store(RequestAnalyticsReportRequest $request, CommandBus $commandBus): JsonResponse
    {
        $data = $request->validated();
        $result = $commandBus->dispatch(new RequestAnalyticsReportCommand(
            type: (string) $data['type'],
            requestedByUserId: (string) $request->user()?->getAuthIdentifier(),
        ));
        assert($result instanceof AsyncJobResponse);

        return response()->json(['data' => $result->toArray()], Response::HTTP_ACCEPTED);
    }
}

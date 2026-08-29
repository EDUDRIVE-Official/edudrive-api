<?php

declare(strict_types=1);

namespace Modules\Integration\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;

final class ExternalReportsPingController
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'data' => [
                'scope' => 'reports.view',
                'message' => 'pong',
            ],
        ]);
    }
}

<?php

declare(strict_types=1);

namespace Modules\Integration\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ExternalStatusController
{
    public function __invoke(Request $request): JsonResponse
    {
        return response()->json([
            'data' => [
                'module' => 'Integration',
                'status' => 'available',
                'consumer_id' => $request->attributes->get('authenticated_api_consumer_id'),
            ],
        ]);
    }
}

<?php

declare(strict_types=1);

namespace Modules\RoadPassport\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

final class RoadPassportStatusController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'data' => [
                'module' => 'RoadPassport',
                'status' => 'available',
                'version' => '1.0.0',
            ],
        ]);
    }
}

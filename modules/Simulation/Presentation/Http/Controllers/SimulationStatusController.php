<?php

declare(strict_types=1);

namespace Modules\Simulation\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

final class SimulationStatusController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'data' => [
                'module' => 'Simulation',
                'status' => 'available',
                'version' => '1.0.0',
            ],
        ]);
    }
}

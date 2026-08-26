<?php

declare(strict_types=1);

namespace Modules\Gamification\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

final class GamificationStatusController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'data' => [
                'module' => 'Gamification',
                'status' => 'available',
                'version' => '1.0.0',
            ],
        ]);
    }
}

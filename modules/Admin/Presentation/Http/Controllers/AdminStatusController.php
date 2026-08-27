<?php

declare(strict_types=1);

namespace Modules\Admin\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

final class AdminStatusController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'data' => [
                'module' => 'Admin',
                'status' => 'available',
                'version' => '1.0.0',
            ],
        ]);
    }
}

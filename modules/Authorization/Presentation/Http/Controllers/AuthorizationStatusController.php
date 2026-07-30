<?php

declare(strict_types=1);

namespace Modules\Authorization\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

final class AuthorizationStatusController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'data' => [
                'module' => 'Authorization',
                'status' => 'available',
                'version' => '1.0.0',
            ],
        ]);
    }
}

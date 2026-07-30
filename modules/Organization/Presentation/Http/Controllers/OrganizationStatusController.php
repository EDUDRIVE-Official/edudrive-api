<?php

declare(strict_types=1);

namespace Modules\Organization\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

final class OrganizationStatusController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'data' => [
                'module' => 'Organization',
                'status' => 'available',
                'version' => '1.0.0',
            ],
        ]);
    }
}

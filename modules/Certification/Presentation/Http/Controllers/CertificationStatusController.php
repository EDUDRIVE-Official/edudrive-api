<?php

declare(strict_types=1);

namespace Modules\Certification\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

final class CertificationStatusController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'data' => [
                'module' => 'Certification',
                'status' => 'available',
                'version' => '1.0.0',
            ],
        ]);
    }
}

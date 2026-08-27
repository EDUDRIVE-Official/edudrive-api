<?php

declare(strict_types=1);

namespace Modules\FileStorage\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

final class FileStorageStatusController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'data' => [
                'module' => 'FileStorage',
                'status' => 'available',
                'version' => '1.0.0',
            ],
        ]);
    }
}

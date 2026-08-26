<?php

declare(strict_types=1);

namespace Modules\Notification\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

final class NotificationStatusController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'data' => [
                'module' => 'Notification',
                'status' => 'available',
                'version' => '1.0.0',
            ],
        ]);
    }
}

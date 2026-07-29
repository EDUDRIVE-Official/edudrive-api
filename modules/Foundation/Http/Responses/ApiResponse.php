<?php

declare(strict_types=1);

namespace Modules\Foundation\Http\Responses;

use Illuminate\Http\JsonResponse;

final class ApiResponse
{
    /**
     * @param  array<string, mixed>  $data
     */
    public static function success(
        array $data = [],
        string $message = 'Operación realizada correctamente.',
        int $status = 200,
    ): JsonResponse {
        return response()->json(
            [
                'success' => true,
                'message' => $message,
                'data' => $data,
            ],
            $status,
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function created(
        array $data = [],
        string $message = 'Recurso creado correctamente.',
    ): JsonResponse {
        return self::success(
            data: $data,
            message: $message,
            status: 201,
        );
    }
}

<?php

declare(strict_types=1);

namespace Modules\Foundation\Presentation\Http\Responses;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Context;
use Throwable;

final class ApiErrorResponse
{
    /**
     * @param  array<string, mixed>|null  $errors
     */
    public static function make(
        string $message,
        int $status,
        string $code,
        ?array $errors = null,
    ): JsonResponse {
        $payload = [
            'success' => false,
            'message' => $message,
            'code' => $code,
            'correlation_id' => Context::get('correlation_id'),
        ];

        if ($errors !== null) {
            $payload['errors'] = $errors;
        }

        return response()->json($payload, $status);
    }

    public static function unexpected(Throwable $exception): JsonResponse
    {
        $payload = [
            'success' => false,
            'message' => 'Ocurrió un error interno inesperado.',
            'code' => 'INTERNAL_SERVER_ERROR',
            'correlation_id' => Context::get('correlation_id'),
        ];

        if (config('app.debug')) {
            $payload['debug'] = [
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ];
        }

        return response()->json($payload, 500);
    }
}

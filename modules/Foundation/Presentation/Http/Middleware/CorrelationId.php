<?php

declare(strict_types=1);

namespace Modules\Foundation\Presentation\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

final class CorrelationId
{
    public const HEADER = 'X-Correlation-ID';

    public function handle(Request $request, Closure $next): Response
    {
        $correlationId = $this->resolveCorrelationId($request);

        $request->headers->set(self::HEADER, $correlationId);

        Context::add('correlation_id', $correlationId);

        $response = $next($request);

        $response->headers->set(self::HEADER, $correlationId);

        return $response;
    }

    private function resolveCorrelationId(Request $request): string
    {
        $correlationId = $request->header(self::HEADER);

        if (is_string($correlationId) && $correlationId !== '') {
            return $correlationId;
        }

        return (string) Str::uuid();
    }
}

<?php

declare(strict_types=1);

namespace Modules\Integration\Presentation\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Foundation\Presentation\Http\Responses\ApiErrorResponse;
use Symfony\Component\HttpFoundation\Response;

final readonly class EnsureApiConsumerScope
{
    public function handle(Request $request, Closure $next, string $scope): Response
    {
        /** @var list<string> $scopes */
        $scopes = $request->attributes->get('authenticated_api_consumer_scopes', []);

        if (! in_array($scope, $scopes, true)) {
            return ApiErrorResponse::make(
                message: "El consumidor de API no tiene el alcance requerido: {$scope}.",
                status: 403,
                code: 'FORBIDDEN',
            );
        }

        return $next($request);
    }
}

<?php

declare(strict_types=1);

namespace Modules\Integration\Presentation\Http\Middleware;

use Closure;
use DateTimeImmutable;
use Illuminate\Http\Request;
use Modules\Foundation\Presentation\Http\Responses\ApiErrorResponse;
use Modules\Integration\Domain\Repositories\ApiConsumerRepository;
use Symfony\Component\HttpFoundation\Response;

final readonly class AuthenticateApiConsumer
{
    public function __construct(
        private ApiConsumerRepository $consumers,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if ($token === null) {
            return $this->unauthorized($request);
        }

        $consumer = $this->consumers->findByIntegrationKeyHash(hash('sha256', $token));

        if ($consumer === null || ! $consumer->isUsableAt(new DateTimeImmutable('now'))) {
            return $this->unauthorized($request);
        }

        $request->attributes->set('authenticated_api_consumer_id', $consumer->id()->value());
        $request->attributes->set('authenticated_api_consumer_scopes', $consumer->scopes());

        return $next($request);
    }

    private function unauthorized(Request $request): Response
    {
        if ($request->is('api/*') || $request->expectsJson()) {
            return ApiErrorResponse::make(
                message: 'Debe autenticarse con una llave de integración de consumidor de API válida.',
                status: 401,
                code: 'UNAUTHENTICATED',
            );
        }

        abort(401);
    }
}

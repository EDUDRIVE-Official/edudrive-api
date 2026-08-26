<?php

declare(strict_types=1);

namespace Modules\Simulation\Presentation\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Foundation\Presentation\Http\Responses\ApiErrorResponse;
use Modules\Simulation\Domain\Enums\SimulatorStatus;
use Modules\Simulation\Domain\Repositories\SimulatorRepository;
use Symfony\Component\HttpFoundation\Response;

final readonly class AuthenticateSimulator
{
    public function __construct(
        private SimulatorRepository $simulators,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if ($token === null) {
            return $this->unauthorized($request);
        }

        $simulator = $this->simulators->findByIntegrationKeyHash(hash('sha256', $token));

        if ($simulator === null || $simulator->status() !== SimulatorStatus::Active) {
            return $this->unauthorized($request);
        }

        $request->attributes->set('authenticated_simulator_id', $simulator->id()->value());

        return $next($request);
    }

    private function unauthorized(Request $request): Response
    {
        if ($request->is('api/*') || $request->expectsJson()) {
            return ApiErrorResponse::make(
                message: 'Debe autenticarse con una llave de integración de simulador válida.',
                status: 401,
                code: 'UNAUTHENTICATED',
            );
        }

        abort(401);
    }
}

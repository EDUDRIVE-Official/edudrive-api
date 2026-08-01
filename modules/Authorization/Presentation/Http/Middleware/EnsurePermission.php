<?php

declare(strict_types=1);

namespace Modules\Authorization\Presentation\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Authorization\Application\Services\PermissionChecker;
use Modules\Authorization\Domain\Enums\Permission;
use Modules\Foundation\Presentation\Http\Responses\ApiErrorResponse;
use Symfony\Component\HttpFoundation\Response;
use ValueError;

/**
 * Must run after an authentication middleware (e.g. `auth:sanctum` or
 * `auth`) that populates `$request->user()` for the correct guard.
 */
final readonly class EnsurePermission
{
    public function __construct(
        private PermissionChecker $checker,
    ) {}

    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        if ($user === null) {
            return $this->respondWithError(
                $request,
                message: 'Debe autenticarse para acceder a este recurso.',
                status: 401,
                code: 'UNAUTHENTICATED',
            );
        }

        try {
            $requiredPermission = Permission::from($permission);
        } catch (ValueError) {
            return $this->respondWithError(
                $request,
                message: 'La configuración de permisos de esta ruta no es válida.',
                status: 500,
                code: 'INVALID_PERMISSION_CONFIGURATION',
            );
        }

        if (! $this->checker->userHasPermission(
            (string) $user->getAuthIdentifier(),
            $requiredPermission,
        )) {
            return $this->respondWithError(
                $request,
                message: 'No tiene permisos para realizar esta acción.',
                status: 403,
                code: 'FORBIDDEN',
            );
        }

        return $next($request);
    }

    private function respondWithError(
        Request $request,
        string $message,
        int $status,
        string $code,
    ): Response {
        if ($request->is('api/*') || $request->expectsJson()) {
            return ApiErrorResponse::make(
                message: $message,
                status: $status,
                code: $code,
            );
        }

        abort($status, $message);
    }
}

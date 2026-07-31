<?php

declare(strict_types=1);

namespace Modules\Authorization\Presentation\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Authorization\Application\Services\PermissionChecker;
use Modules\Authorization\Domain\Enums\Permission;
use Modules\Foundation\Presentation\Http\Responses\ApiErrorResponse;
use Symfony\Component\HttpFoundation\Response;

final readonly class EnsurePermission
{
    public function __construct(
        private PermissionChecker $checker,
    ) {}

    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        if ($user === null) {
            return ApiErrorResponse::make(
                message: 'Debe autenticarse para acceder a este recurso.',
                status: 401,
                code: 'UNAUTHENTICATED',
            );
        }

        $requiredPermission = Permission::from($permission);

        if (! $this->checker->userHasPermission(
            (string) $user->getAuthIdentifier(),
            $requiredPermission,
        )) {
            return ApiErrorResponse::make(
                message: 'No tiene permisos para realizar esta acción.',
                status: 403,
                code: 'FORBIDDEN',
            );
        }

        return $next($request);
    }
}

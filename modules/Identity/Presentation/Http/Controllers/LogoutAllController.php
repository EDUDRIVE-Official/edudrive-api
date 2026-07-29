<?php

declare(strict_types=1);

namespace Modules\Identity\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Foundation\Http\Responses\ApiResponse;
use Modules\Identity\Application\UseCases\LogoutAllUsersUseCase;

final readonly class LogoutAllController
{
    public function __construct(
        private LogoutAllUsersUseCase $useCase,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $authenticatedUser = $request->user();

        if ($authenticatedUser === null) {
            abort(401);
        }

        $this->useCase->execute(
            (string) $authenticatedUser->getAuthIdentifier(),
        );

        return ApiResponse::success(
            message: 'Todas las sesiones han sido cerradas correctamente.',
        );
    }
}

<?php

declare(strict_types=1);

namespace Modules\Identity\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Foundation\Http\Responses\ApiResponse;
use Modules\Identity\Application\UseCases\RevokeSessionUseCase;

final readonly class RevokeSessionController
{
    public function __construct(
        private RevokeSessionUseCase $useCase,
    ) {}

    public function __invoke(Request $request, string $tokenId): JsonResponse
    {
        $authenticatedUser = $request->user();

        if ($authenticatedUser === null) {
            abort(401);
        }

        $this->useCase->execute(
            tokenId: $tokenId,
            userId: (string) $authenticatedUser->getAuthIdentifier(),
        );

        return ApiResponse::success(
            message: 'Sesión revocada correctamente.',
        );
    }
}

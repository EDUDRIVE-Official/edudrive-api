<?php

declare(strict_types=1);

namespace Modules\Identity\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Foundation\Http\Responses\ApiResponse;
use Modules\Identity\Application\DTO\SessionData;
use Modules\Identity\Application\UseCases\GetUserSessionsUseCase;

final readonly class SessionsController
{
    public function __construct(
        private GetUserSessionsUseCase $useCase,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $authenticatedUser = $request->user();

        if ($authenticatedUser === null) {
            abort(401);
        }

        $currentToken = $authenticatedUser->currentAccessToken();

        $sessions = $this->useCase->execute(
            userId: (string) $authenticatedUser->getAuthIdentifier(),
            currentTokenId: (string) $currentToken->getKey(),
        );

        return ApiResponse::success(
            data: [
                'sessions' => array_map(
                    static fn (SessionData $session): array => [
                        'id' => $session->id,
                        'name' => $session->name,
                        'current' => $session->current,
                        'last_used_at' => $session->lastUsedAt,
                        'created_at' => $session->createdAt,
                    ],
                    $sessions,
                ),
            ],
        );
    }
}

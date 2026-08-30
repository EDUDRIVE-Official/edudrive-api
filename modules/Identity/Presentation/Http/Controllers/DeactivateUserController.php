<?php

declare(strict_types=1);

namespace Modules\Identity\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Identity\Application\Commands\DeactivateUserCommand;
use Modules\Identity\Application\UseCases\DeactivateUserUseCase;

final class DeactivateUserController extends Controller
{
    public function __construct(
        private readonly DeactivateUserUseCase $useCase,
    ) {}

    public function __invoke(Request $request, string $userId): JsonResponse
    {
        $response = $this->useCase->execute(
            new DeactivateUserCommand(
                userId: $userId,
                actorId: (string) $request->user()?->getAuthIdentifier(),
            ),
        );

        return response()->json([
            'success' => true,
            'message' => $response->message,
            'data' => [
                'id' => $response->userId,
                'status' => $response->status,
            ],
        ]);
    }
}

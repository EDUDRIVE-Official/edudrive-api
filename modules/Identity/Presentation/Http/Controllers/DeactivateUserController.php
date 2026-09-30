<?php

declare(strict_types=1);

namespace Modules\Identity\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Authorization\Domain\Enums\Permission;
use Modules\Identity\Application\Commands\DeactivateUserCommand;
use Modules\Identity\Application\Services\UserAdministrationScope;
use Modules\Identity\Application\UseCases\DeactivateUserUseCase;

final class DeactivateUserController extends Controller
{
    public function __construct(
        private readonly DeactivateUserUseCase $useCase,
        private readonly UserAdministrationScope $scope,
    ) {}

    public function __invoke(Request $request, string $userId): JsonResponse
    {
        abort_unless($this->scope->canAccess((string) $request->user()?->getAuthIdentifier(), $userId, Permission::ManageUsers), 404);
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

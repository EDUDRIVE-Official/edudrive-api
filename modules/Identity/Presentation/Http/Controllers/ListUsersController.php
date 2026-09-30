<?php

declare(strict_types=1);

namespace Modules\Identity\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Authorization\Domain\Enums\Permission;
use Modules\Identity\Application\Responses\UserResponse;
use Modules\Identity\Application\Services\UserAdministrationScope;
use Modules\Identity\Application\UseCases\ListUsersUseCase;

final class ListUsersController extends Controller
{
    public function __construct(
        private readonly ListUsersUseCase $useCase,
        private readonly UserAdministrationScope $scope,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $actorId = (string) $request->user()?->getAuthIdentifier();
        $users = array_values(array_filter(
            $this->useCase->execute(),
            fn (UserResponse $user): bool => $this->scope->canAccess($actorId, $user->id, Permission::ViewUsers),
        ));

        return response()->json([
            'data' => array_map(
                static fn (UserResponse $user): array => $user->toArray(),
                $users,
            ),
        ]);
    }
}

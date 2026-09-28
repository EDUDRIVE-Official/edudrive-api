<?php

declare(strict_types=1);

namespace Modules\Identity\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Authorization\Domain\Enums\Permission;
use Modules\Identity\Application\Services\UserAdministrationScope;
use Modules\Identity\Application\UseCases\GetUserUseCase;

final class ShowUserController extends Controller
{
    public function __construct(
        private readonly GetUserUseCase $useCase,
        private readonly UserAdministrationScope $scope,
    ) {}

    public function __invoke(Request $request, string $userId): JsonResponse
    {
        abort_unless($this->scope->canAccess((string) $request->user()?->getAuthIdentifier(), $userId, Permission::ViewUsers), 404);
        $user = $this->useCase->execute($userId);

        return response()->json(['data' => $user->toArray()]);
    }
}

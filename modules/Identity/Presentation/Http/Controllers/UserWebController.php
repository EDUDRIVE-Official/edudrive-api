<?php

declare(strict_types=1);

namespace Modules\Identity\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Authorization\Application\Services\PermissionChecker;
use Modules\Authorization\Domain\Enums\Permission;
use Modules\Identity\Application\Commands\ActivateUserCommand;
use Modules\Identity\Application\Commands\DeactivateUserCommand;
use Modules\Identity\Application\Responses\UserResponse;
use Modules\Identity\Application\UseCases\ActivateUserUseCase;
use Modules\Identity\Application\UseCases\DeactivateUserUseCase;
use Modules\Identity\Application\UseCases\ListUsersUseCase;

final class UserWebController extends Controller
{
    public function __construct(
        private readonly ListUsersUseCase $listUsers,
        private readonly ActivateUserUseCase $activateUser,
        private readonly DeactivateUserUseCase $deactivateUser,
    ) {}

    public function index(PermissionChecker $checker): View
    {
        return view('users.index', [
            'users' => array_map(
                static fn (UserResponse $user): array => $user->toArray(),
                $this->listUsers->execute(),
            ),
            'canManage' => $checker->userHasPermission(
                (string) auth()->id(),
                Permission::ManageUsers,
            ),
        ]);
    }

    public function activate(Request $request, string $userId): RedirectResponse
    {
        $response = $this->activateUser->execute(
            new ActivateUserCommand(
                userId: $userId,
                actorId: (string) $request->user()?->getAuthIdentifier(),
            ),
        );

        return redirect()
            ->route('users.index')
            ->with('status', $response->message);
    }

    public function deactivate(Request $request, string $userId): RedirectResponse
    {
        $response = $this->deactivateUser->execute(
            new DeactivateUserCommand(
                userId: $userId,
                actorId: (string) $request->user()?->getAuthIdentifier(),
            ),
        );

        return redirect()
            ->route('users.index')
            ->with('status', $response->message);
    }
}

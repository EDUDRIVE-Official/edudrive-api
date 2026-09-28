<?php

declare(strict_types=1);

namespace Modules\RoadPassport\Presentation\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Authorization\Application\Services\PermissionChecker;
use Modules\Authorization\Domain\Enums\Permission;
use Modules\Authorization\Domain\Enums\Role;
use Modules\Authorization\Infrastructure\Persistence\Eloquent\Models\RoleAssignmentModel;
use Modules\Foundation\Application\Bus\CommandBus;
use Modules\Foundation\Application\Bus\QueryBus;
use Modules\Foundation\Domain\Exceptions\DomainException;
use Modules\Identity\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Modules\RoadPassport\Application\Commands\ChangeRoadPassportLevelCommand;
use Modules\RoadPassport\Application\Commands\IssueRoadPassportCommand;
use Modules\RoadPassport\Application\Commands\ReactivateRoadPassportCommand;
use Modules\RoadPassport\Application\Commands\RevokeRoadPassportCommand;
use Modules\RoadPassport\Application\Commands\SuspendRoadPassportCommand;
use Modules\RoadPassport\Application\Exceptions\RoadPassportNotFound;
use Modules\RoadPassport\Application\Queries\GetRoadPassportByUserIdQuery;
use Modules\RoadPassport\Application\Responses\RoadPassportResponse;
use Modules\RoadPassport\Presentation\Http\Requests\ChangeRoadPassportLevelRequest;
use Modules\RoadPassport\Presentation\Http\Requests\IssueRoadPassportRequest;
use Modules\RoadPassport\Presentation\Http\Requests\RevokeRoadPassportRequest;
use Modules\RoadPassport\Presentation\Http\Requests\SuspendRoadPassportRequest;

final class RoadPassportAdminWebController
{
    public function search(
        Request $request,
        QueryBus $queryBus,
        PermissionChecker $checker,
    ): View {
        $userId = $request->query('user_id');
        $userId = is_string($userId) && $userId !== '' ? $userId : null;

        $passport = null;
        $notFound = false;

        if ($userId !== null) {
            try {
                $result = $queryBus->ask(new GetRoadPassportByUserIdQuery(userId: $userId));
                assert($result instanceof RoadPassportResponse);
                $passport = $result->toArray();
            } catch (RoadPassportNotFound) {
                $notFound = true;
            }
        }

        return view('road-passport.admin', [
            'searchedUserId' => $userId,
            'passport' => $passport,
            'notFound' => $notFound,
            'canManage' => $checker->userHasPermission(
                (string) auth()->id(),
                Permission::ManageRoadPassports,
            ),
            'users' => UserModel::query()
                ->whereIn('id', RoleAssignmentModel::query()->select('user_id')->where('role', Role::Student->value))
                ->orderBy('name')
                ->get(['id', 'name', 'email', 'status']),
            'selectedUserName' => $userId === null ? null : UserModel::query()->whereKey($userId)->value('name'),
        ]);
    }

    public function issue(IssueRoadPassportRequest $request, CommandBus $commandBus): RedirectResponse
    {
        $userId = (string) $request->validated()['user_id'];

        try {
            $commandBus->dispatch(new IssueRoadPassportCommand(userId: $userId));
        } catch (DomainException $exception) {
            return $this->back($userId, error: $exception->getMessage());
        }

        return $this->back($userId, status: 'Pasaporte vial emitido correctamente.');
    }

    public function suspend(SuspendRoadPassportRequest $request, string $roadPassportId, CommandBus $commandBus): RedirectResponse
    {
        $userId = (string) $request->input('user_id');
        $data = $request->validated();

        try {
            $commandBus->dispatch(new SuspendRoadPassportCommand(
                roadPassportId: $roadPassportId,
                reason: isset($data['reason']) ? (string) $data['reason'] : null,
            ));
        } catch (DomainException $exception) {
            return $this->back($userId, error: $exception->getMessage());
        }

        return $this->back($userId, status: 'Pasaporte suspendido correctamente.');
    }

    public function reactivate(Request $request, string $roadPassportId, CommandBus $commandBus): RedirectResponse
    {
        $userId = (string) $request->input('user_id');

        try {
            $commandBus->dispatch(new ReactivateRoadPassportCommand(roadPassportId: $roadPassportId));
        } catch (DomainException $exception) {
            return $this->back($userId, error: $exception->getMessage());
        }

        return $this->back($userId, status: 'Pasaporte reactivado correctamente.');
    }

    public function revoke(RevokeRoadPassportRequest $request, string $roadPassportId, CommandBus $commandBus): RedirectResponse
    {
        $userId = (string) $request->input('user_id');
        $data = $request->validated();

        try {
            $commandBus->dispatch(new RevokeRoadPassportCommand(
                roadPassportId: $roadPassportId,
                reason: isset($data['reason']) ? (string) $data['reason'] : null,
            ));
        } catch (DomainException $exception) {
            return $this->back($userId, error: $exception->getMessage());
        }

        return $this->back($userId, status: 'Pasaporte revocado correctamente.');
    }

    public function changeLevel(ChangeRoadPassportLevelRequest $request, string $roadPassportId, CommandBus $commandBus): RedirectResponse
    {
        $userId = (string) $request->input('user_id');
        $data = $request->validated();

        try {
            $commandBus->dispatch(new ChangeRoadPassportLevelCommand(
                roadPassportId: $roadPassportId,
                level: (int) $data['level'],
            ));
        } catch (DomainException $exception) {
            return $this->back($userId, error: $exception->getMessage());
        }

        return $this->back($userId, status: 'Nivel actualizado correctamente.');
    }

    private function back(string $userId, ?string $status = null, ?string $error = null): RedirectResponse
    {
        $redirect = redirect()->route('road-passport.admin.search', ['user_id' => $userId]);

        if ($status !== null) {
            $redirect->with('status', $status);
        }

        if ($error !== null) {
            $redirect->with('error', $error);
        }

        return $redirect;
    }
}

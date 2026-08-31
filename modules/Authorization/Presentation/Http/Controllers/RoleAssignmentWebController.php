<?php

declare(strict_types=1);

namespace Modules\Authorization\Presentation\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Modules\Authorization\Application\Commands\AssignRoleCommand;
use Modules\Authorization\Domain\Enums\Role;
use Modules\Authorization\Presentation\Http\Requests\AssignRoleRequest;
use Modules\Foundation\Application\Bus\CommandBus;

final class RoleAssignmentWebController
{
    public function create(): View
    {
        return view('roles.assign', [
            'roles' => Role::cases(),
        ]);
    }

    public function store(
        AssignRoleRequest $request,
        CommandBus $commandBus,
    ): RedirectResponse {
        $validated = $request->validated();

        $commandBus->dispatch(
            new AssignRoleCommand(
                userId: (string) $validated['user_id'],
                role: (string) $validated['role'],
                organizationId: isset($validated['organization_id'])
                    ? (string) $validated['organization_id']
                    : null,
                actorId: (string) $request->user()?->getAuthIdentifier(),
            ),
        );

        return redirect()
            ->route('roles.assign')
            ->with('status', 'Rol asignado correctamente.');
    }
}

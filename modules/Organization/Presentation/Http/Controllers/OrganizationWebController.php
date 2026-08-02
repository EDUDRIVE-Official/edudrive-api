<?php

declare(strict_types=1);

namespace Modules\Organization\Presentation\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Modules\Authorization\Application\Services\PermissionChecker;
use Modules\Authorization\Domain\Enums\Permission;
use Modules\Foundation\Application\Bus\CommandBus;
use Modules\Foundation\Application\Bus\QueryBus;
use Modules\Organization\Application\Commands\CreateOrganizationCommand;
use Modules\Organization\Application\Queries\ListOrganizationsQuery;
use Modules\Organization\Application\Responses\OrganizationListItemResponse;
use Modules\Organization\Domain\Enums\OrganizationType;
use Modules\Organization\Presentation\Http\Requests\CreateOrganizationRequest;

final class OrganizationWebController
{
    public function index(
        QueryBus $queryBus,
        PermissionChecker $checker,
    ): View {
        $result = $queryBus->ask(
            new ListOrganizationsQuery,
        );

        assert(is_array($result));

        /** @var list<OrganizationListItemResponse> $result */
        $organizations = array_map(
            static fn (OrganizationListItemResponse $organization): array => $organization->toArray(),
            $result,
        );

        return view('organizations.index', [
            'organizations' => $organizations,
            'canManage' => $checker->userHasPermission(
                (string) auth()->id(),
                Permission::ManageOrganizations,
            ),
        ]);
    }

    public function create(): View
    {
        return view('organizations.create', [
            'types' => OrganizationType::cases(),
        ]);
    }

    public function store(
        CreateOrganizationRequest $request,
        CommandBus $commandBus,
    ): RedirectResponse {
        $validated = $request->validated();

        $commandBus->dispatch(
            new CreateOrganizationCommand(
                name: (string) $validated['name'],
                type: (string) $validated['type'],
            ),
        );

        return redirect()
            ->route('organizations.index')
            ->with('status', 'Organización creada correctamente.');
    }
}

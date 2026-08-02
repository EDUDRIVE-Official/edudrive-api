<?php

declare(strict_types=1);

namespace Modules\Organization\Presentation\Http\Controllers;

use Illuminate\Contracts\View\View;
use Modules\Authorization\Application\Services\PermissionChecker;
use Modules\Authorization\Domain\Enums\Permission;
use Modules\Foundation\Application\Bus\QueryBus;
use Modules\Organization\Application\Queries\ListOrganizationsQuery;
use Modules\Organization\Application\Responses\OrganizationListItemResponse;

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
}

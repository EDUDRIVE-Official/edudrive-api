<?php

declare(strict_types=1);

namespace Modules\Organization\Presentation\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Modules\Authorization\Application\Services\AccessibleOrganizationsResolver;
use Modules\Authorization\Application\Services\PermissionChecker;
use Modules\Authorization\Domain\Enums\Permission;
use Modules\Authorization\Domain\Enums\Role;
use Modules\Authorization\Infrastructure\Persistence\Eloquent\Models\RoleAssignmentModel;
use Modules\Foundation\Application\Bus\CommandBus;
use Modules\Foundation\Application\Bus\QueryBus;
use Modules\Identity\Infrastructure\Persistence\Eloquent\Models\OrganizationMembershipRequestModel;
use Modules\Identity\Infrastructure\Persistence\Eloquent\Models\UserModel;
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
        AccessibleOrganizationsResolver $accessibleOrganizations,
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

        $userId = (string) auth()->id();
        $visibleOrganizationIds = $accessibleOrganizations->resolveForPermission($userId, Permission::ViewOrganizations);
        if ($visibleOrganizationIds !== null) {
            $organizations = array_values(array_filter(
                $organizations,
                static fn (array $organization): bool => in_array($organization['id'], $visibleOrganizationIds, true),
            ));
        }
        $organizationIds = array_column($organizations, 'id');
        $assignments = RoleAssignmentModel::query()
            ->whereIn('organization_id', $organizationIds)
            ->whereIn('role', [Role::InstitutionalAdmin->value, Role::Student->value])
            ->get(['user_id', 'organization_id', 'role']);
        $usersById = UserModel::query()
            ->whereIn('id', $assignments->pluck('user_id')->unique())
            ->get(['id', 'name', 'email'])
            ->keyBy('id');
        $assignmentsByOrganization = $assignments->groupBy('organization_id');
        $organizations = array_map(static function (array $organization) use ($assignmentsByOrganization, $usersById): array {
            $organizationAssignments = $assignmentsByOrganization->get($organization['id'], collect());
            $organization['student_count'] = $organizationAssignments
                ->where('role', Role::Student->value)->pluck('user_id')->unique()->count();
            $organization['administrators'] = $organizationAssignments
                ->where('role', Role::InstitutionalAdmin->value)
                ->pluck('user_id')->unique()
                ->map(static function (string $administratorId) use ($usersById): array {
                    $administrator = $usersById->get($administratorId);

                    return [
                        'name' => $administrator?->name ?? 'Cuenta no disponible',
                        'email' => $administrator?->email,
                    ];
                })->values()->all();

            return $organization;
        }, $organizations);

        $canManage = $checker->userHasPermission($userId, Permission::ManageOrganizations);
        $canReviewMemberships = $checker->userHasPermission($userId, Permission::ManageUsers);
        $allowedOrganizationIds = $accessibleOrganizations->resolveForPermission($userId, Permission::ManageUsers);
        $membershipQuery = OrganizationMembershipRequestModel::query()->where('status', 'pending')->orderBy('requested_at');
        if ($allowedOrganizationIds !== null) {
            $membershipQuery->whereIn('organization_id', $allowedOrganizationIds);
        }
        $organizationNames = collect($organizations)->pluck('name', 'id');
        $membershipRequests = $canReviewMemberships ? $membershipQuery->get()->map(fn (OrganizationMembershipRequestModel $item): array => [
            'id' => (string) $item->id,
            'student_name' => UserModel::query()->whereKey($item->user_id)->value('name') ?? 'Estudiante',
            'organization_name' => $organizationNames->get((string) $item->organization_id, 'Organización'),
            'requested_at' => $item->requested_at?->format('d/m/Y H:i'),
        ])->all() : [];

        return view('organizations.index', [
            'organizations' => $organizations,
            'canManage' => $canManage,
            'canReviewMemberships' => $canReviewMemberships,
            'membershipRequests' => $membershipRequests,
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

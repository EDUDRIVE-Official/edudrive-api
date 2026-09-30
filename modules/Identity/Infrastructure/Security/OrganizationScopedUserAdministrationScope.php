<?php

declare(strict_types=1);

namespace Modules\Identity\Infrastructure\Security;

use Modules\Authorization\Application\Services\AccessibleOrganizationsResolver;
use Modules\Authorization\Domain\Enums\Permission;
use Modules\Authorization\Infrastructure\Persistence\Eloquent\Models\RoleAssignmentModel;
use Modules\Identity\Application\Services\UserAdministrationScope;

final readonly class OrganizationScopedUserAdministrationScope implements UserAdministrationScope
{
    public function __construct(private AccessibleOrganizationsResolver $organizations) {}

    public function canAccess(string $actorUserId, string $targetUserId, Permission $permission): bool
    {
        $organizationIds = $this->organizations->resolveForPermission($actorUserId, $permission);
        if ($organizationIds === null) {
            return true;
        }

        return $organizationIds !== [] && RoleAssignmentModel::query()
            ->where('user_id', $targetUserId)
            ->whereIn('organization_id', $organizationIds)
            ->exists();
    }

    public function hasGlobalAccess(string $actorUserId, Permission $permission): bool
    {
        return $this->organizations->resolveForPermission($actorUserId, $permission) === null;
    }
}

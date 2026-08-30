<?php

declare(strict_types=1);

namespace Modules\Authorization\Infrastructure\Services;

use Modules\Authorization\Application\Services\AccessibleOrganizationsResolver;
use Modules\Authorization\Domain\Enums\Permission;
use Modules\Authorization\Domain\Repositories\RoleAssignmentRepository;
use Modules\Authorization\Domain\Services\RolePermissions;

final readonly class RoleAssignmentAccessibleOrganizationsResolver implements AccessibleOrganizationsResolver
{
    public function __construct(
        private RoleAssignmentRepository $assignments,
    ) {}

    public function resolveForPermission(string $userId, Permission $permission): ?array
    {
        $organizationIds = [];

        foreach ($this->assignments->findByUserId($userId) as $assignment) {
            if (! RolePermissions::grants($assignment->role(), $permission)) {
                continue;
            }

            if ($assignment->organizationId() === null) {
                return null;
            }

            $organizationIds[] = $assignment->organizationId();
        }

        return array_values(array_unique($organizationIds));
    }
}

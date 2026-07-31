<?php

declare(strict_types=1);

namespace Modules\Authorization\Infrastructure\Services;

use Modules\Authorization\Application\Services\PermissionChecker;
use Modules\Authorization\Domain\Enums\Permission;
use Modules\Authorization\Domain\Repositories\RoleAssignmentRepository;
use Modules\Authorization\Domain\Services\RolePermissions;

final readonly class RoleAssignmentPermissionChecker implements PermissionChecker
{
    public function __construct(
        private RoleAssignmentRepository $assignments,
    ) {}

    public function userHasPermission(string $userId, Permission $permission): bool
    {
        foreach ($this->assignments->findByUserId($userId) as $assignment) {
            if (RolePermissions::grants($assignment->role(), $permission)) {
                return true;
            }
        }

        return false;
    }
}

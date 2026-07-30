<?php

declare(strict_types=1);

namespace Modules\Authorization\Domain\Services;

use Modules\Authorization\Domain\Enums\Permission;
use Modules\Authorization\Domain\Enums\Role;

final class RolePermissions
{
    /**
     * @var array<string, list<Permission>>
     */
    private const array MAP = [
        'super_admin' => [
            Permission::ManageOrganizations,
            Permission::ViewOrganizations,
            Permission::ManageRoleAssignments,
        ],
        'institutional_admin' => [
            Permission::ViewOrganizations,
        ],
        'teacher' => [
            Permission::ViewOrganizations,
        ],
        'student' => [
            Permission::ViewOrganizations,
        ],
    ];

    public static function grants(Role $role, Permission $permission): bool
    {
        return in_array($permission, self::MAP[$role->value], true);
    }
}

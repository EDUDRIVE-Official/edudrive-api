<?php

declare(strict_types=1);

namespace Modules\Authorization\Domain\Enums;

enum Permission: string
{
    case ManageOrganizations = 'organizations.manage';
    case ViewOrganizations = 'organizations.view';
    case ManageRoleAssignments = 'roles.manage';
}

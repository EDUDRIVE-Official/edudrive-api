<?php

declare(strict_types=1);

namespace Modules\Authorization\Application\Services;

use Modules\Authorization\Domain\Enums\Permission;

interface PermissionChecker
{
    public function userHasPermission(string $userId, Permission $permission): bool;
}

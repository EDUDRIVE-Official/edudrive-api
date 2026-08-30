<?php

declare(strict_types=1);

namespace Modules\Authorization\Application\Services;

use Modules\Authorization\Domain\Enums\Permission;

interface AccessibleOrganizationsResolver
{
    /**
     * @return list<string>|null null significa sin restricción (todas las organizaciones)
     */
    public function resolveForPermission(string $userId, Permission $permission): ?array;
}

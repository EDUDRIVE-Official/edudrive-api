<?php

declare(strict_types=1);

namespace Modules\Identity\Application\Services;

use Modules\Authorization\Domain\Enums\Permission;

interface UserAdministrationScope
{
    public function canAccess(string $actorUserId, string $targetUserId, Permission $permission): bool;

    public function hasGlobalAccess(string $actorUserId, Permission $permission): bool;
}

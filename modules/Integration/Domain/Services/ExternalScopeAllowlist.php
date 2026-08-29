<?php

declare(strict_types=1);

namespace Modules\Integration\Domain\Services;

use Modules\Authorization\Domain\Enums\Permission;

final class ExternalScopeAllowlist
{
    public static function allows(string $scope): bool
    {
        $permission = Permission::tryFrom($scope);

        return $permission !== null && in_array($permission, self::permissions(), true);
    }

    /** @return list<Permission> */
    private static function permissions(): array
    {
        return [
            Permission::ManageEnrollments,
            Permission::ViewEnrollments,
            Permission::ViewCertifications,
            Permission::ViewRoadPassports,
            Permission::ViewReports,
        ];
    }
}

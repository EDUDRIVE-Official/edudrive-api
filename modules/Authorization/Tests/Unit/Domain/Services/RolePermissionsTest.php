<?php

declare(strict_types=1);

use Modules\Authorization\Domain\Enums\Permission;
use Modules\Authorization\Domain\Enums\Role;
use Modules\Authorization\Domain\Services\RolePermissions;

it('otorga todos los permisos definidos al superadministrador', function (): void {
    expect(RolePermissions::grants(Role::SuperAdmin, Permission::ManageOrganizations))->toBeTrue()
        ->and(RolePermissions::grants(Role::SuperAdmin, Permission::ViewOrganizations))->toBeTrue()
        ->and(RolePermissions::grants(Role::SuperAdmin, Permission::ManageRoleAssignments))->toBeTrue();
});

it('solo otorga permisos de visualización a administradores institucionales, docentes y estudiantes', function (): void {
    expect(RolePermissions::grants(Role::InstitutionalAdmin, Permission::ViewOrganizations))->toBeTrue()
        ->and(RolePermissions::grants(Role::InstitutionalAdmin, Permission::ManageOrganizations))->toBeFalse()
        ->and(RolePermissions::grants(Role::Teacher, Permission::ViewOrganizations))->toBeTrue()
        ->and(RolePermissions::grants(Role::Teacher, Permission::ManageRoleAssignments))->toBeFalse()
        ->and(RolePermissions::grants(Role::Student, Permission::ViewOrganizations))->toBeTrue()
        ->and(RolePermissions::grants(Role::Student, Permission::ManageOrganizations))->toBeFalse();
});

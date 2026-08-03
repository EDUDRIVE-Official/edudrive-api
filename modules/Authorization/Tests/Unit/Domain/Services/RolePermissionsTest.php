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
        ->and(RolePermissions::grants(Role::InstitutionalAdmin, Permission::ManageRoleAssignments))->toBeFalse()
        ->and(RolePermissions::grants(Role::Teacher, Permission::ViewOrganizations))->toBeTrue()
        ->and(RolePermissions::grants(Role::Teacher, Permission::ManageOrganizations))->toBeFalse()
        ->and(RolePermissions::grants(Role::Teacher, Permission::ManageRoleAssignments))->toBeFalse()
        ->and(RolePermissions::grants(Role::Student, Permission::ViewOrganizations))->toBeTrue()
        ->and(RolePermissions::grants(Role::Student, Permission::ManageOrganizations))->toBeFalse()
        ->and(RolePermissions::grants(Role::Student, Permission::ManageRoleAssignments))->toBeFalse();
});

it('otorga los permisos de cursos al superadministrador', function (): void {
    expect(RolePermissions::grants(Role::SuperAdmin, Permission::ManageCourses))->toBeTrue()
        ->and(RolePermissions::grants(Role::SuperAdmin, Permission::ViewCourses))->toBeTrue();
});

it('solo otorga permiso de visualización de cursos a administradores institucionales, docentes y estudiantes', function (): void {
    expect(RolePermissions::grants(Role::InstitutionalAdmin, Permission::ViewCourses))->toBeTrue()
        ->and(RolePermissions::grants(Role::InstitutionalAdmin, Permission::ManageCourses))->toBeFalse()
        ->and(RolePermissions::grants(Role::Teacher, Permission::ViewCourses))->toBeTrue()
        ->and(RolePermissions::grants(Role::Teacher, Permission::ManageCourses))->toBeFalse()
        ->and(RolePermissions::grants(Role::Student, Permission::ViewCourses))->toBeTrue()
        ->and(RolePermissions::grants(Role::Student, Permission::ManageCourses))->toBeFalse();
});

it('otorga gestión y consulta de competencias al superadministrador', function (): void {
    expect(RolePermissions::grants(Role::SuperAdmin, Permission::ManageCompetencies))->toBeTrue()
        ->and(RolePermissions::grants(Role::SuperAdmin, Permission::ViewCompetencies))->toBeTrue();
});

it('solo otorga consulta de competencias a los demás roles', function (): void {
    expect(RolePermissions::grants(Role::InstitutionalAdmin, Permission::ViewCompetencies))->toBeTrue()
        ->and(RolePermissions::grants(Role::InstitutionalAdmin, Permission::ManageCompetencies))->toBeFalse()
        ->and(RolePermissions::grants(Role::Teacher, Permission::ViewCompetencies))->toBeTrue()
        ->and(RolePermissions::grants(Role::Teacher, Permission::ManageCompetencies))->toBeFalse()
        ->and(RolePermissions::grants(Role::Student, Permission::ViewCompetencies))->toBeTrue()
        ->and(RolePermissions::grants(Role::Student, Permission::ManageCompetencies))->toBeFalse();
});

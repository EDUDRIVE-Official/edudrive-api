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

it('otorga gestión y consulta de programas al superadministrador', function (): void {
    expect(RolePermissions::grants(Role::SuperAdmin, Permission::ManagePrograms))->toBeTrue()
        ->and(RolePermissions::grants(Role::SuperAdmin, Permission::ViewPrograms))->toBeTrue();
});

it('solo otorga consulta de programas a los demás roles', function (): void {
    expect(RolePermissions::grants(Role::InstitutionalAdmin, Permission::ViewPrograms))->toBeTrue()
        ->and(RolePermissions::grants(Role::InstitutionalAdmin, Permission::ManagePrograms))->toBeFalse()
        ->and(RolePermissions::grants(Role::Teacher, Permission::ViewPrograms))->toBeTrue()
        ->and(RolePermissions::grants(Role::Teacher, Permission::ManagePrograms))->toBeFalse()
        ->and(RolePermissions::grants(Role::Student, Permission::ViewPrograms))->toBeTrue()
        ->and(RolePermissions::grants(Role::Student, Permission::ManagePrograms))->toBeFalse();
});

it('otorga gestión y consulta de preguntas al superadministrador', function (): void {
    expect(RolePermissions::grants(Role::SuperAdmin, Permission::ManageQuestions))->toBeTrue()
        ->and(RolePermissions::grants(Role::SuperAdmin, Permission::ViewQuestions))->toBeTrue();
});

it('solo otorga consulta de preguntas a los demás roles', function (): void {
    expect(RolePermissions::grants(Role::InstitutionalAdmin, Permission::ViewQuestions))->toBeTrue()
        ->and(RolePermissions::grants(Role::InstitutionalAdmin, Permission::ManageQuestions))->toBeFalse()
        ->and(RolePermissions::grants(Role::Teacher, Permission::ViewQuestions))->toBeTrue()
        ->and(RolePermissions::grants(Role::Teacher, Permission::ManageQuestions))->toBeFalse()
        ->and(RolePermissions::grants(Role::Student, Permission::ViewQuestions))->toBeTrue()
        ->and(RolePermissions::grants(Role::Student, Permission::ManageQuestions))->toBeFalse();
});

it('otorga gestión y consulta de exámenes al superadministrador', function (): void {
    expect(RolePermissions::grants(Role::SuperAdmin, Permission::ManageExams))->toBeTrue()
        ->and(RolePermissions::grants(Role::SuperAdmin, Permission::ViewExams))->toBeTrue();
});

it('solo otorga consulta de exámenes a los demás roles', function (): void {
    expect(RolePermissions::grants(Role::InstitutionalAdmin, Permission::ViewExams))->toBeTrue()
        ->and(RolePermissions::grants(Role::InstitutionalAdmin, Permission::ManageExams))->toBeFalse()
        ->and(RolePermissions::grants(Role::Teacher, Permission::ViewExams))->toBeTrue()
        ->and(RolePermissions::grants(Role::Teacher, Permission::ManageExams))->toBeFalse()
        ->and(RolePermissions::grants(Role::Student, Permission::ViewExams))->toBeTrue()
        ->and(RolePermissions::grants(Role::Student, Permission::ManageExams))->toBeFalse();
});

it('otorga consulta de intentos de evaluación al superadministrador', function (): void {
    expect(RolePermissions::grants(Role::SuperAdmin, Permission::ViewExamAttempts))->toBeTrue();
});

it('otorga consulta de intentos de evaluación a administradores institucionales y docentes, pero no a estudiantes', function (): void {
    expect(RolePermissions::grants(Role::InstitutionalAdmin, Permission::ViewExamAttempts))->toBeTrue()
        ->and(RolePermissions::grants(Role::Teacher, Permission::ViewExamAttempts))->toBeTrue()
        ->and(RolePermissions::grants(Role::Student, Permission::ViewExamAttempts))->toBeFalse();
});

it('otorga permisos de enrollments al superadministrador', function (): void {
    expect(RolePermissions::grants(Role::SuperAdmin, Permission::ManageEnrollments))->toBeTrue()
        ->and(RolePermissions::grants(Role::SuperAdmin, Permission::ViewEnrollments))->toBeTrue();
});

it('otorga manage y view de enrollments al administrador institucional, solo view al docente y ninguno al estudiante', function (): void {
    expect(RolePermissions::grants(Role::InstitutionalAdmin, Permission::ManageEnrollments))->toBeTrue()
        ->and(RolePermissions::grants(Role::InstitutionalAdmin, Permission::ViewEnrollments))->toBeTrue()
        ->and(RolePermissions::grants(Role::Teacher, Permission::ManageEnrollments))->toBeFalse()
        ->and(RolePermissions::grants(Role::Teacher, Permission::ViewEnrollments))->toBeTrue()
        ->and(RolePermissions::grants(Role::Student, Permission::ManageEnrollments))->toBeFalse()
        ->and(RolePermissions::grants(Role::Student, Permission::ViewEnrollments))->toBeFalse();
});

it('otorga permisos de pasaporte vial al superadministrador', function (): void {
    expect(RolePermissions::grants(Role::SuperAdmin, Permission::ManageRoadPassports))->toBeTrue()
        ->and(RolePermissions::grants(Role::SuperAdmin, Permission::ViewRoadPassports))->toBeTrue();
});

it('otorga manage y view de pasaporte vial al administrador institucional, solo view al docente y ninguno al estudiante', function (): void {
    expect(RolePermissions::grants(Role::InstitutionalAdmin, Permission::ManageRoadPassports))->toBeTrue()
        ->and(RolePermissions::grants(Role::InstitutionalAdmin, Permission::ViewRoadPassports))->toBeTrue()
        ->and(RolePermissions::grants(Role::Teacher, Permission::ManageRoadPassports))->toBeFalse()
        ->and(RolePermissions::grants(Role::Teacher, Permission::ViewRoadPassports))->toBeTrue()
        ->and(RolePermissions::grants(Role::Student, Permission::ManageRoadPassports))->toBeFalse()
        ->and(RolePermissions::grants(Role::Student, Permission::ViewRoadPassports))->toBeFalse();
});

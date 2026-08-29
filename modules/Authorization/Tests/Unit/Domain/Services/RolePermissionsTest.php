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

it('otorga permisos de certificaciones al superadministrador', function (): void {
    expect(RolePermissions::grants(Role::SuperAdmin, Permission::ManageCertifications))->toBeTrue()
        ->and(RolePermissions::grants(Role::SuperAdmin, Permission::ViewCertifications))->toBeTrue();
});

it('otorga manage y view de certificaciones al administrador institucional, solo view al docente y ninguno al estudiante', function (): void {
    expect(RolePermissions::grants(Role::InstitutionalAdmin, Permission::ManageCertifications))->toBeTrue()
        ->and(RolePermissions::grants(Role::InstitutionalAdmin, Permission::ViewCertifications))->toBeTrue()
        ->and(RolePermissions::grants(Role::Teacher, Permission::ManageCertifications))->toBeFalse()
        ->and(RolePermissions::grants(Role::Teacher, Permission::ViewCertifications))->toBeTrue()
        ->and(RolePermissions::grants(Role::Student, Permission::ManageCertifications))->toBeFalse()
        ->and(RolePermissions::grants(Role::Student, Permission::ViewCertifications))->toBeFalse();
});

it('otorga permisos de simuladores al superadministrador', function (): void {
    expect(RolePermissions::grants(Role::SuperAdmin, Permission::ManageSimulators))->toBeTrue()
        ->and(RolePermissions::grants(Role::SuperAdmin, Permission::ViewSimulators))->toBeTrue();
});

it('otorga manage y view de simuladores al administrador institucional, solo view al docente y ninguno al estudiante', function (): void {
    expect(RolePermissions::grants(Role::InstitutionalAdmin, Permission::ManageSimulators))->toBeTrue()
        ->and(RolePermissions::grants(Role::InstitutionalAdmin, Permission::ViewSimulators))->toBeTrue()
        ->and(RolePermissions::grants(Role::Teacher, Permission::ManageSimulators))->toBeFalse()
        ->and(RolePermissions::grants(Role::Teacher, Permission::ViewSimulators))->toBeTrue()
        ->and(RolePermissions::grants(Role::Student, Permission::ManageSimulators))->toBeFalse()
        ->and(RolePermissions::grants(Role::Student, Permission::ViewSimulators))->toBeFalse();
});

it('otorga permisos de sesiones de simulacion al superadministrador', function (): void {
    expect(RolePermissions::grants(Role::SuperAdmin, Permission::ManageSimulationSessions))->toBeTrue()
        ->and(RolePermissions::grants(Role::SuperAdmin, Permission::ViewSimulationSessions))->toBeTrue();
});

it('otorga manage y view de sesiones de simulacion al administrador institucional, solo view al docente y ninguno al estudiante', function (): void {
    expect(RolePermissions::grants(Role::InstitutionalAdmin, Permission::ManageSimulationSessions))->toBeTrue()
        ->and(RolePermissions::grants(Role::InstitutionalAdmin, Permission::ViewSimulationSessions))->toBeTrue()
        ->and(RolePermissions::grants(Role::Teacher, Permission::ManageSimulationSessions))->toBeFalse()
        ->and(RolePermissions::grants(Role::Teacher, Permission::ViewSimulationSessions))->toBeTrue()
        ->and(RolePermissions::grants(Role::Student, Permission::ManageSimulationSessions))->toBeFalse()
        ->and(RolePermissions::grants(Role::Student, Permission::ViewSimulationSessions))->toBeFalse();
});

it('otorga permisos de logros al superadministrador', function (): void {
    expect(RolePermissions::grants(Role::SuperAdmin, Permission::ManageAchievements))->toBeTrue()
        ->and(RolePermissions::grants(Role::SuperAdmin, Permission::ViewAchievements))->toBeTrue();
});

it('otorga manage y view de logros al administrador institucional, y view al docente y al estudiante', function (): void {
    expect(RolePermissions::grants(Role::InstitutionalAdmin, Permission::ManageAchievements))->toBeTrue()
        ->and(RolePermissions::grants(Role::InstitutionalAdmin, Permission::ViewAchievements))->toBeTrue()
        ->and(RolePermissions::grants(Role::Teacher, Permission::ManageAchievements))->toBeFalse()
        ->and(RolePermissions::grants(Role::Teacher, Permission::ViewAchievements))->toBeTrue()
        ->and(RolePermissions::grants(Role::Student, Permission::ManageAchievements))->toBeFalse()
        ->and(RolePermissions::grants(Role::Student, Permission::ViewAchievements))->toBeTrue();
});

it('otorga permisos de insignias al superadministrador', function (): void {
    expect(RolePermissions::grants(Role::SuperAdmin, Permission::ManageBadges))->toBeTrue()
        ->and(RolePermissions::grants(Role::SuperAdmin, Permission::ViewBadges))->toBeTrue();
});

it('otorga manage y view de insignias al administrador institucional, y view al docente y al estudiante', function (): void {
    expect(RolePermissions::grants(Role::InstitutionalAdmin, Permission::ManageBadges))->toBeTrue()
        ->and(RolePermissions::grants(Role::InstitutionalAdmin, Permission::ViewBadges))->toBeTrue()
        ->and(RolePermissions::grants(Role::Teacher, Permission::ManageBadges))->toBeFalse()
        ->and(RolePermissions::grants(Role::Teacher, Permission::ViewBadges))->toBeTrue()
        ->and(RolePermissions::grants(Role::Student, Permission::ManageBadges))->toBeFalse()
        ->and(RolePermissions::grants(Role::Student, Permission::ViewBadges))->toBeTrue();
});

it('otorga el permiso de gestion de experiencia al superadministrador y al administrador institucional, y a nadie mas', function (): void {
    expect(RolePermissions::grants(Role::SuperAdmin, Permission::ManageExperience))->toBeTrue()
        ->and(RolePermissions::grants(Role::InstitutionalAdmin, Permission::ManageExperience))->toBeTrue()
        ->and(RolePermissions::grants(Role::Teacher, Permission::ManageExperience))->toBeFalse()
        ->and(RolePermissions::grants(Role::Student, Permission::ManageExperience))->toBeFalse();
});

it('otorga manage y view de retos al administrador institucional, y view al docente y al estudiante', function (): void {
    expect(RolePermissions::grants(Role::InstitutionalAdmin, Permission::ManageChallenges))->toBeTrue()
        ->and(RolePermissions::grants(Role::InstitutionalAdmin, Permission::ViewChallenges))->toBeTrue()
        ->and(RolePermissions::grants(Role::Teacher, Permission::ManageChallenges))->toBeFalse()
        ->and(RolePermissions::grants(Role::Teacher, Permission::ViewChallenges))->toBeTrue()
        ->and(RolePermissions::grants(Role::Student, Permission::ManageChallenges))->toBeFalse()
        ->and(RolePermissions::grants(Role::Student, Permission::ViewChallenges))->toBeTrue();
});

it('otorga permisos de retos al superadministrador', function (): void {
    expect(RolePermissions::grants(Role::SuperAdmin, Permission::ManageChallenges))->toBeTrue()
        ->and(RolePermissions::grants(Role::SuperAdmin, Permission::ViewChallenges))->toBeTrue();
});

it('otorga el permiso de gestion de notificaciones al superadministrador y al administrador institucional, y a nadie mas', function (): void {
    expect(RolePermissions::grants(Role::SuperAdmin, Permission::ManageNotifications))->toBeTrue()
        ->and(RolePermissions::grants(Role::InstitutionalAdmin, Permission::ManageNotifications))->toBeTrue()
        ->and(RolePermissions::grants(Role::Teacher, Permission::ManageNotifications))->toBeFalse()
        ->and(RolePermissions::grants(Role::Student, Permission::ManageNotifications))->toBeFalse();
});

it('otorga manage y view de plantillas de comunicacion al administrador institucional, y view al docente pero no al estudiante', function (): void {
    expect(RolePermissions::grants(Role::InstitutionalAdmin, Permission::ManageCommunicationTemplates))->toBeTrue()
        ->and(RolePermissions::grants(Role::InstitutionalAdmin, Permission::ViewCommunicationTemplates))->toBeTrue()
        ->and(RolePermissions::grants(Role::Teacher, Permission::ManageCommunicationTemplates))->toBeFalse()
        ->and(RolePermissions::grants(Role::Teacher, Permission::ViewCommunicationTemplates))->toBeTrue()
        ->and(RolePermissions::grants(Role::Student, Permission::ManageCommunicationTemplates))->toBeFalse()
        ->and(RolePermissions::grants(Role::Student, Permission::ViewCommunicationTemplates))->toBeFalse();
});

it('otorga permisos de plantillas de comunicacion al superadministrador', function (): void {
    expect(RolePermissions::grants(Role::SuperAdmin, Permission::ManageCommunicationTemplates))->toBeTrue()
        ->and(RolePermissions::grants(Role::SuperAdmin, Permission::ViewCommunicationTemplates))->toBeTrue();
});

it('otorga manage, view de usuarios y view de reportes al superadministrador y al administrador institucional, y a nadie mas', function (): void {
    expect(RolePermissions::grants(Role::SuperAdmin, Permission::ManageUsers))->toBeTrue()
        ->and(RolePermissions::grants(Role::SuperAdmin, Permission::ViewUsers))->toBeTrue()
        ->and(RolePermissions::grants(Role::SuperAdmin, Permission::ViewReports))->toBeTrue()
        ->and(RolePermissions::grants(Role::InstitutionalAdmin, Permission::ManageUsers))->toBeTrue()
        ->and(RolePermissions::grants(Role::InstitutionalAdmin, Permission::ViewUsers))->toBeTrue()
        ->and(RolePermissions::grants(Role::InstitutionalAdmin, Permission::ViewReports))->toBeTrue()
        ->and(RolePermissions::grants(Role::Teacher, Permission::ManageUsers))->toBeFalse()
        ->and(RolePermissions::grants(Role::Teacher, Permission::ViewUsers))->toBeFalse()
        ->and(RolePermissions::grants(Role::Teacher, Permission::ViewReports))->toBeFalse()
        ->and(RolePermissions::grants(Role::Student, Permission::ManageUsers))->toBeFalse()
        ->and(RolePermissions::grants(Role::Student, Permission::ViewUsers))->toBeFalse()
        ->and(RolePermissions::grants(Role::Student, Permission::ViewReports))->toBeFalse();
});

it('otorga manage y view de archivos ajenos al superadministrador y al administrador institucional, y a nadie mas', function (): void {
    expect(RolePermissions::grants(Role::SuperAdmin, Permission::ManageFiles))->toBeTrue()
        ->and(RolePermissions::grants(Role::SuperAdmin, Permission::ViewFiles))->toBeTrue()
        ->and(RolePermissions::grants(Role::InstitutionalAdmin, Permission::ManageFiles))->toBeTrue()
        ->and(RolePermissions::grants(Role::InstitutionalAdmin, Permission::ViewFiles))->toBeTrue()
        ->and(RolePermissions::grants(Role::Teacher, Permission::ManageFiles))->toBeFalse()
        ->and(RolePermissions::grants(Role::Teacher, Permission::ViewFiles))->toBeFalse()
        ->and(RolePermissions::grants(Role::Student, Permission::ManageFiles))->toBeFalse()
        ->and(RolePermissions::grants(Role::Student, Permission::ViewFiles))->toBeFalse();
});

it('otorga exports.view al superadministrador y al administrador institucional, y a nadie mas', function (): void {
    expect(RolePermissions::grants(Role::SuperAdmin, Permission::ViewExports))->toBeTrue()
        ->and(RolePermissions::grants(Role::InstitutionalAdmin, Permission::ViewExports))->toBeTrue()
        ->and(RolePermissions::grants(Role::Teacher, Permission::ViewExports))->toBeFalse()
        ->and(RolePermissions::grants(Role::Student, Permission::ViewExports))->toBeFalse();
});

it('otorga permisos de configuracion y operacion del sistema unicamente al superadministrador', function (): void {
    expect(RolePermissions::grants(Role::SuperAdmin, Permission::ManageSystemSettings))->toBeTrue()
        ->and(RolePermissions::grants(Role::SuperAdmin, Permission::ViewSystemSettings))->toBeTrue()
        ->and(RolePermissions::grants(Role::SuperAdmin, Permission::ViewSystemOperations))->toBeTrue()
        ->and(RolePermissions::grants(Role::InstitutionalAdmin, Permission::ManageSystemSettings))->toBeFalse()
        ->and(RolePermissions::grants(Role::InstitutionalAdmin, Permission::ViewSystemSettings))->toBeFalse()
        ->and(RolePermissions::grants(Role::InstitutionalAdmin, Permission::ViewSystemOperations))->toBeFalse()
        ->and(RolePermissions::grants(Role::Teacher, Permission::ManageSystemSettings))->toBeFalse()
        ->and(RolePermissions::grants(Role::Student, Permission::ViewSystemOperations))->toBeFalse();
});

it('otorga la gestion de politicas legales unicamente al superadministrador', function (): void {
    expect(RolePermissions::grants(Role::SuperAdmin, Permission::ManageLegalPolicies))->toBeTrue()
        ->and(RolePermissions::grants(Role::InstitutionalAdmin, Permission::ManageLegalPolicies))->toBeFalse()
        ->and(RolePermissions::grants(Role::Teacher, Permission::ManageLegalPolicies))->toBeFalse()
        ->and(RolePermissions::grants(Role::Student, Permission::ManageLegalPolicies))->toBeFalse();
});

it('otorga la consulta de consentimientos por organizacion al superadministrador y al administrador institucional', function (): void {
    expect(RolePermissions::grants(Role::SuperAdmin, Permission::ViewOrganizationConsents))->toBeTrue()
        ->and(RolePermissions::grants(Role::InstitutionalAdmin, Permission::ViewOrganizationConsents))->toBeTrue()
        ->and(RolePermissions::grants(Role::Teacher, Permission::ViewOrganizationConsents))->toBeFalse()
        ->and(RolePermissions::grants(Role::Student, Permission::ViewOrganizationConsents))->toBeFalse();
});

it('otorga la gestion y consulta de consumidores de api unicamente al superadministrador', function (): void {
    expect(RolePermissions::grants(Role::SuperAdmin, Permission::ManageApiConsumers))->toBeTrue()
        ->and(RolePermissions::grants(Role::SuperAdmin, Permission::ViewApiConsumers))->toBeTrue()
        ->and(RolePermissions::grants(Role::InstitutionalAdmin, Permission::ManageApiConsumers))->toBeFalse()
        ->and(RolePermissions::grants(Role::InstitutionalAdmin, Permission::ViewApiConsumers))->toBeFalse()
        ->and(RolePermissions::grants(Role::Teacher, Permission::ManageApiConsumers))->toBeFalse()
        ->and(RolePermissions::grants(Role::Student, Permission::ManageApiConsumers))->toBeFalse();
});

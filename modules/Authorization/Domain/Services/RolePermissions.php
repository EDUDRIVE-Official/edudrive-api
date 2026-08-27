<?php

declare(strict_types=1);

namespace Modules\Authorization\Domain\Services;

use Modules\Authorization\Domain\Enums\Permission;
use Modules\Authorization\Domain\Enums\Role;

final class RolePermissions
{
    public static function grants(Role $role, Permission $permission): bool
    {
        return in_array($permission, self::permissionsFor($role), true);
    }

    /**
     * @return list<Permission>
     */
    private static function permissionsFor(Role $role): array
    {
        return match ($role) {
            Role::SuperAdmin => [
                Permission::ManageOrganizations,
                Permission::ViewOrganizations,
                Permission::ManageRoleAssignments,
                Permission::ManageCourses,
                Permission::ViewCourses,
                Permission::ManageCompetencies,
                Permission::ViewCompetencies,
                Permission::ManagePrograms,
                Permission::ViewPrograms,
                Permission::ManageQuestions,
                Permission::ViewQuestions,
                Permission::ManageExams,
                Permission::ViewExams,
                Permission::ViewExamAttempts,
                Permission::ManageEnrollments,
                Permission::ViewEnrollments,
                Permission::ManageRoadPassports,
                Permission::ViewRoadPassports,
                Permission::ManageCertifications,
                Permission::ViewCertifications,
                Permission::ManageSimulators,
                Permission::ViewSimulators,
                Permission::ManageSimulationSessions,
                Permission::ViewSimulationSessions,
                Permission::ManageAchievements,
                Permission::ViewAchievements,
                Permission::ManageBadges,
                Permission::ViewBadges,
                Permission::ManageExperience,
                Permission::ManageChallenges,
                Permission::ViewChallenges,
                Permission::ManageNotifications,
                Permission::ManageCommunicationTemplates,
                Permission::ViewCommunicationTemplates,
            ],
            Role::InstitutionalAdmin => [
                Permission::ViewOrganizations,
                Permission::ViewCourses,
                Permission::ViewCompetencies,
                Permission::ViewPrograms,
                Permission::ViewQuestions,
                Permission::ViewExams,
                Permission::ViewExamAttempts,
                Permission::ManageEnrollments,
                Permission::ViewEnrollments,
                Permission::ManageRoadPassports,
                Permission::ViewRoadPassports,
                Permission::ManageCertifications,
                Permission::ViewCertifications,
                Permission::ManageSimulators,
                Permission::ViewSimulators,
                Permission::ManageSimulationSessions,
                Permission::ViewSimulationSessions,
                Permission::ManageAchievements,
                Permission::ViewAchievements,
                Permission::ManageBadges,
                Permission::ViewBadges,
                Permission::ManageExperience,
                Permission::ManageChallenges,
                Permission::ViewChallenges,
                Permission::ManageNotifications,
                Permission::ManageCommunicationTemplates,
                Permission::ViewCommunicationTemplates,
            ],
            Role::Teacher => [
                Permission::ViewOrganizations,
                Permission::ViewCourses,
                Permission::ViewCompetencies,
                Permission::ViewPrograms,
                Permission::ViewQuestions,
                Permission::ViewExams,
                Permission::ViewExamAttempts,
                Permission::ViewEnrollments,
                Permission::ViewRoadPassports,
                Permission::ViewCertifications,
                Permission::ViewSimulators,
                Permission::ViewSimulationSessions,
                Permission::ViewAchievements,
                Permission::ViewBadges,
                Permission::ViewChallenges,
                Permission::ViewCommunicationTemplates,
            ],
            Role::Student => [
                Permission::ViewOrganizations,
                Permission::ViewCourses,
                Permission::ViewCompetencies,
                Permission::ViewPrograms,
                Permission::ViewQuestions,
                Permission::ViewExams,
                Permission::ViewAchievements,
                Permission::ViewBadges,
                Permission::ViewChallenges,
            ],
        };
    }
}

<?php

declare(strict_types=1);

namespace Modules\Authorization\Domain\Enums;

enum Permission: string
{
    case ManageOrganizations = 'organizations.manage';
    case ViewOrganizations = 'organizations.view';
    case ManageRoleAssignments = 'roles.manage';
    case ManageCourses = 'courses.manage';
    case ViewCourses = 'courses.view';
    case ManageCompetencies = 'competencies.manage';
    case ViewCompetencies = 'competencies.view';
    case ManagePrograms = 'programs.manage';
    case ViewPrograms = 'programs.view';
    case ManageQuestions = 'questions.manage';
    case ViewQuestions = 'questions.view';
}

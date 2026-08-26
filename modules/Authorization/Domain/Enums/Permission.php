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
    case ManageExams = 'exams.manage';
    case ViewExams = 'exams.view';
    case ViewExamAttempts = 'exam_attempts.view';
    case ManageEnrollments = 'enrollments.manage';
    case ViewEnrollments = 'enrollments.view';
    case ManageRoadPassports = 'road_passports.manage';
    case ViewRoadPassports = 'road_passports.view';
    case ManageCertifications = 'certifications.manage';
    case ViewCertifications = 'certifications.view';
    case ManageSimulators = 'simulators.manage';
    case ViewSimulators = 'simulators.view';
    case ManageSimulationSessions = 'simulation_sessions.manage';
    case ViewSimulationSessions = 'simulation_sessions.view';
    case ManageAchievements = 'achievements.manage';
    case ViewAchievements = 'achievements.view';
    case ManageBadges = 'badges.manage';
    case ViewBadges = 'badges.view';
    case ManageExperience = 'experience.manage';
}

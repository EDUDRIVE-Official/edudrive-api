<?php

declare(strict_types=1);

namespace Modules\RoadPassport\Domain\Enums;

enum EvidenceType: string
{
    case LessonCompleted = 'lesson_completed';
    case GuidedPracticeObserved = 'guided_practice_observed';
    case SelfReportedPractice = 'self_reported_practice';
    case StudentReflection = 'student_reflection';
    case CourseCompleted = 'course_completed';
    case ExamPassed = 'exam_passed';
}

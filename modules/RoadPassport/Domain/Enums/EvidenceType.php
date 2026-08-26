<?php

declare(strict_types=1);

namespace Modules\RoadPassport\Domain\Enums;

enum EvidenceType: string
{
    case CourseCompleted = 'course_completed';
    case ExamPassed = 'exam_passed';
}

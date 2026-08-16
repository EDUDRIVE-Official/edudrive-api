<?php

declare(strict_types=1);

namespace Modules\Learning\Domain\ValueObjects;

enum LearningVerb: string
{
    case LessonCompleted = 'lesson_completed';
    case ExamAttemptSubmitted = 'exam_attempt_submitted';
}

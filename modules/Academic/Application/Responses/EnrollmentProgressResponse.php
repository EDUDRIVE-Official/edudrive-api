<?php

declare(strict_types=1);

namespace Modules\Academic\Application\Responses;

final readonly class EnrollmentProgressResponse
{
    /** @param list<string> $completedLessons */
    public function __construct(
        public string $enrollmentId,
        public string $courseId,
        public string $userId,
        public array $completedLessons,
        public int $completedLessonsCount,
        public int $totalLessons,
        public int $progressPercentage,
        public int $timeSpentMinutes,
        public int $evaluationsCompleted,
        public ?string $lastActivityAt,
    ) {}

    /** @return array{enrollment_id: string, course_id: string, user_id: string, completed_lessons: list<string>, completed_lessons_count: int, total_lessons: int, progress_percentage: int, time_spent_minutes: int, evaluations_completed: int, last_activity_at: string|null} */
    public function toArray(): array
    {
        return [
            'enrollment_id' => $this->enrollmentId,
            'course_id' => $this->courseId,
            'user_id' => $this->userId,
            'completed_lessons' => $this->completedLessons,
            'completed_lessons_count' => $this->completedLessonsCount,
            'total_lessons' => $this->totalLessons,
            'progress_percentage' => $this->progressPercentage,
            'time_spent_minutes' => $this->timeSpentMinutes,
            'evaluations_completed' => $this->evaluationsCompleted,
            'last_activity_at' => $this->lastActivityAt,
        ];
    }
}

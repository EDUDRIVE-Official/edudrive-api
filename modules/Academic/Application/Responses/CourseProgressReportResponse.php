<?php

declare(strict_types=1);

namespace Modules\Academic\Application\Responses;

final readonly class CourseProgressReportResponse
{
    public function __construct(
        public string $courseId,
        public string $courseCode,
        public string $courseTitle,
        public int $enrollmentCount,
        public float $averageCompletionPercentage,
        public int $fullyCompletedCount,
    ) {}

    /** @return array{course_id: string, course_code: string, course_title: string, enrollment_count: int, average_completion_percentage: float, fully_completed_count: int} */
    public function toArray(): array
    {
        return [
            'course_id' => $this->courseId,
            'course_code' => $this->courseCode,
            'course_title' => $this->courseTitle,
            'enrollment_count' => $this->enrollmentCount,
            'average_completion_percentage' => $this->averageCompletionPercentage,
            'fully_completed_count' => $this->fullyCompletedCount,
        ];
    }
}

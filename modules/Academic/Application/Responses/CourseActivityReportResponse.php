<?php

declare(strict_types=1);

namespace Modules\Academic\Application\Responses;

final readonly class CourseActivityReportResponse
{
    public function __construct(
        public string $courseId,
        public string $courseCode,
        public string $courseTitle,
        public int $enrollmentCount,
        public int $activeLast30DaysCount,
        public int $neverLoggedInCount,
        public ?float $averageDaysSinceLastLogin,
    ) {}

    /** @return array{course_id: string, course_code: string, course_title: string, enrollment_count: int, active_last_30_days_count: int, never_logged_in_count: int, average_days_since_last_login: float|null} */
    public function toArray(): array
    {
        return [
            'course_id' => $this->courseId,
            'course_code' => $this->courseCode,
            'course_title' => $this->courseTitle,
            'enrollment_count' => $this->enrollmentCount,
            'active_last_30_days_count' => $this->activeLast30DaysCount,
            'never_logged_in_count' => $this->neverLoggedInCount,
            'average_days_since_last_login' => $this->averageDaysSinceLastLogin,
        ];
    }
}

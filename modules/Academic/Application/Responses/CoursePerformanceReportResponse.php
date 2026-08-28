<?php

declare(strict_types=1);

namespace Modules\Academic\Application\Responses;

final readonly class CoursePerformanceReportResponse
{
    public function __construct(
        public string $courseId,
        public string $courseCode,
        public string $courseTitle,
        public int $attemptCount,
        public float $averageScore,
        public float $averagePercentage,
    ) {}

    /** @return array{course_id: string, course_code: string, course_title: string, attempt_count: int, average_score: float, average_percentage: float} */
    public function toArray(): array
    {
        return [
            'course_id' => $this->courseId,
            'course_code' => $this->courseCode,
            'course_title' => $this->courseTitle,
            'attempt_count' => $this->attemptCount,
            'average_score' => $this->averageScore,
            'average_percentage' => $this->averagePercentage,
        ];
    }
}

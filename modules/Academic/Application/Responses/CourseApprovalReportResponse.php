<?php

declare(strict_types=1);

namespace Modules\Academic\Application\Responses;

final readonly class CourseApprovalReportResponse
{
    public function __construct(
        public string $courseId,
        public string $courseCode,
        public string $courseTitle,
        public int $attemptCount,
        public int $passedCount,
        public float $passRate,
    ) {}

    /** @return array{course_id: string, course_code: string, course_title: string, attempt_count: int, passed_count: int, pass_rate: float} */
    public function toArray(): array
    {
        return [
            'course_id' => $this->courseId,
            'course_code' => $this->courseCode,
            'course_title' => $this->courseTitle,
            'attempt_count' => $this->attemptCount,
            'passed_count' => $this->passedCount,
            'pass_rate' => $this->passRate,
        ];
    }
}

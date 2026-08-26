<?php

declare(strict_types=1);

namespace Modules\Academic\Application\Responses;

final readonly class TheoryExamAttemptListItemResponse
{
    public function __construct(
        public string $id,
        public string $examId,
        public string $userId,
        public string $status,
        public string $startedAt,
        public ?string $submittedAt,
        public int $score,
        public int $percentage,
        public bool $passed,
        public string $licenseCategory,
    ) {}

    /** @return array{id: string, exam_id: string, user_id: string, status: string, started_at: string, submitted_at: string|null, score: int, percentage: int, passed: bool, license_category: string} */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'exam_id' => $this->examId,
            'user_id' => $this->userId,
            'status' => $this->status,
            'started_at' => $this->startedAt,
            'submitted_at' => $this->submittedAt,
            'score' => $this->score,
            'percentage' => $this->percentage,
            'passed' => $this->passed,
            'license_category' => $this->licenseCategory,
        ];
    }
}

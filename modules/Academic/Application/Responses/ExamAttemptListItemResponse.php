<?php

declare(strict_types=1);

namespace Modules\Academic\Application\Responses;

use Modules\Academic\Domain\Aggregates\ExamAttempt;

final readonly class ExamAttemptListItemResponse
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
    ) {}

    public static function fromAttempt(ExamAttempt $attempt): self
    {
        return new self(
            $attempt->id()->value(),
            $attempt->examId()->value(),
            $attempt->userId(),
            $attempt->status()->value,
            $attempt->startedAt()->format(DATE_ATOM),
            $attempt->submittedAt()?->format(DATE_ATOM),
            $attempt->score(),
            $attempt->percentage(),
            $attempt->passed(),
        );
    }

    /**
     * @return array{id: string, exam_id: string, user_id: string, status: string, started_at: string, submitted_at: string|null, score: int, percentage: int, passed: bool}
     */
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
        ];
    }
}

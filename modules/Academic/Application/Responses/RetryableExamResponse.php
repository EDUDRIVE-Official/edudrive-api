<?php

declare(strict_types=1);

namespace Modules\Academic\Application\Responses;

final readonly class RetryableExamResponse
{
    public function __construct(
        public string $examId,
        public string $title,
        public int $lastPercentage,
        public int $passingScore,
        public int $attemptsUsed,
        public int $maxAttempts,
    ) {}

    /** @return array{exam_id: string, title: string, last_percentage: int, passing_score: int, attempts_used: int, max_attempts: int} */
    public function toArray(): array
    {
        return [
            'exam_id' => $this->examId,
            'title' => $this->title,
            'last_percentage' => $this->lastPercentage,
            'passing_score' => $this->passingScore,
            'attempts_used' => $this->attemptsUsed,
            'max_attempts' => $this->maxAttempts,
        ];
    }
}

<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\Entities;

use Modules\Academic\Domain\Exceptions\InvalidExam;
use Modules\Academic\Domain\ValueObjects\QuestionId;

final readonly class ExamQuestion
{
    private function __construct(
        private int $position,
        private QuestionId $questionId,
        private int $points,
    ) {}

    public static function create(int $position, QuestionId $questionId, int $points): self
    {
        if ($position < 1 || $points < 1) {
            throw InvalidExam::create();
        }

        return new self($position, $questionId, $points);
    }

    public function position(): int
    {
        return $this->position;
    }

    public function questionId(): QuestionId
    {
        return $this->questionId;
    }

    public function points(): int
    {
        return $this->points;
    }
}

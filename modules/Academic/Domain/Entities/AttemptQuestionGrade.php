<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\Entities;

use InvalidArgumentException;
use Modules\Academic\Domain\ValueObjects\AttemptQuestionId;
use Modules\Academic\Domain\ValueObjects\CompetencyId;
use Modules\Academic\Domain\ValueObjects\QuestionId;

final readonly class AttemptQuestionGrade
{
    public function __construct(
        private AttemptQuestionId $attemptQuestionId,
        private QuestionId $questionId,
        private CompetencyId $competencyId,
        private int $score,
        private int $totalPoints,
        private int $percentage,
        private bool $isCorrect,
        private bool $isAnswered,
    ) {
        if ($this->score < 0) {
            throw new InvalidArgumentException('El puntaje de la pregunta no puede ser negativo.');
        }

        if ($this->totalPoints < 1) {
            throw new InvalidArgumentException('Los puntos totales de la pregunta deben ser mayores que cero.');
        }

        if ($this->score > $this->totalPoints) {
            throw new InvalidArgumentException('El puntaje de la pregunta no puede superar los puntos totales.');
        }

        if ($this->percentage < 0 || $this->percentage > 100) {
            throw new InvalidArgumentException('El porcentaje de la pregunta debe estar entre 0 y 100.');
        }

        $expectedPercentage = (int) round($this->score / $this->totalPoints * 100);
        if ($this->percentage !== $expectedPercentage) {
            throw new InvalidArgumentException('El porcentaje de la pregunta debe coincidir con el puntaje y los puntos totales.');
        }
    }

    public function attemptQuestionId(): AttemptQuestionId
    {
        return $this->attemptQuestionId;
    }

    public function questionId(): QuestionId
    {
        return $this->questionId;
    }

    public function competencyId(): CompetencyId
    {
        return $this->competencyId;
    }

    public function score(): int
    {
        return $this->score;
    }

    public function totalPoints(): int
    {
        return $this->totalPoints;
    }

    public function percentage(): int
    {
        return $this->percentage;
    }

    public function isCorrect(): bool
    {
        return $this->isCorrect;
    }

    public function isAnswered(): bool
    {
        return $this->isAnswered;
    }

    /** @return array<string, string|int|bool> */
    public function toArray(): array
    {
        return [
            'attempt_question_id' => $this->attemptQuestionId->value(),
            'question_id' => $this->questionId->value(),
            'competency_id' => $this->competencyId->value(),
            'score' => $this->score,
            'total_points' => $this->totalPoints,
            'percentage' => $this->percentage,
            'is_correct' => $this->isCorrect,
            'is_answered' => $this->isAnswered,
        ];
    }
}

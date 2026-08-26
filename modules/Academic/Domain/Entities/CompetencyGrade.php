<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\Entities;

use InvalidArgumentException;
use Modules\Academic\Domain\ValueObjects\CompetencyId;

final readonly class CompetencyGrade
{
    public function __construct(
        private CompetencyId $competencyId,
        private int $score,
        private int $totalPoints,
        private int $percentage,
    ) {
        if ($this->score < 0) {
            throw new InvalidArgumentException('El puntaje de la competencia no puede ser negativo.');
        }

        if ($this->totalPoints < 1) {
            throw new InvalidArgumentException('Los puntos totales de la competencia deben ser mayores que cero.');
        }

        if ($this->score > $this->totalPoints) {
            throw new InvalidArgumentException('El puntaje de la competencia no puede superar los puntos totales.');
        }

        if ($this->percentage < 0 || $this->percentage > 100) {
            throw new InvalidArgumentException('El porcentaje de la competencia debe estar entre 0 y 100.');
        }

        $expectedPercentage = (int) round($this->score / $this->totalPoints * 100);
        if ($this->percentage !== $expectedPercentage) {
            throw new InvalidArgumentException('El porcentaje de la competencia debe coincidir con el puntaje y los puntos totales.');
        }
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

    /** @return array<string, string|int> */
    public function toArray(): array
    {
        return [
            'competency_id' => $this->competencyId->value(),
            'score' => $this->score,
            'total_points' => $this->totalPoints,
            'percentage' => $this->percentage,
        ];
    }
}

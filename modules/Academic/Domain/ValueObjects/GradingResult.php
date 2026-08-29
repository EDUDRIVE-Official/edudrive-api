<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\ValueObjects;

use InvalidArgumentException;
use Modules\Academic\Domain\Entities\AttemptQuestionGrade;
use Modules\Academic\Domain\Entities\CompetencyGrade;

final readonly class GradingResult
{
    /**
     * @param  list<AttemptQuestionGrade>  $questionBreakdown
     * @param  list<CompetencyGrade>  $competencyBreakdown
     */
    public function __construct(
        private int $score,
        private int $totalPoints,
        private int $percentage,
        private bool $passed,
        private array $questionBreakdown,
        private array $competencyBreakdown,
    ) {
        if ($this->score < 0) {
            throw new InvalidArgumentException('El puntaje total no puede ser negativo.');
        }

        if ($this->totalPoints < 1) {
            throw new InvalidArgumentException('Los puntos totales deben ser mayores que cero.');
        }

        if ($this->score > $this->totalPoints) {
            throw new InvalidArgumentException('El puntaje total no puede superar los puntos totales.');
        }

        if ($this->percentage < 0 || $this->percentage > 100) {
            throw new InvalidArgumentException('El porcentaje total debe estar entre 0 y 100.');
        }

        if ($this->questionBreakdown === []) {
            throw new InvalidArgumentException('El breakdown por pregunta no puede estar vacio.');
        }

        if ($this->competencyBreakdown === []) {
            throw new InvalidArgumentException('El breakdown por competencia no puede estar vacio.');
        }

        foreach ($this->questionBreakdown as $questionGrade) {
            // @phpstan-ignore instanceof.alwaysTrue (runtime guard: el docblock `list<AttemptQuestionGrade>` no lo impone PHP en tiempo de ejecucion)
            if (! $questionGrade instanceof AttemptQuestionGrade) {
                throw new InvalidArgumentException('El breakdown por pregunta contiene un elemento invalido.');
            }
        }

        foreach ($this->competencyBreakdown as $competencyGrade) {
            // @phpstan-ignore instanceof.alwaysTrue (runtime guard: el docblock `list<CompetencyGrade>` no lo impone PHP en tiempo de ejecucion)
            if (! $competencyGrade instanceof CompetencyGrade) {
                throw new InvalidArgumentException('El breakdown por competencia contiene un elemento invalido.');
            }
        }

        $questionScore = array_sum(array_map(
            static fn (AttemptQuestionGrade $grade): int => $grade->score(),
            $this->questionBreakdown,
        ));
        $questionTotalPoints = array_sum(array_map(
            static fn (AttemptQuestionGrade $grade): int => $grade->totalPoints(),
            $this->questionBreakdown,
        ));

        if ($this->score !== $questionScore) {
            throw new InvalidArgumentException('El puntaje total debe coincidir con el breakdown por pregunta.');
        }

        if ($this->totalPoints !== $questionTotalPoints) {
            throw new InvalidArgumentException('Los puntos totales deben coincidir con el breakdown por pregunta.');
        }

        $competencyScore = array_sum(array_map(
            static fn (CompetencyGrade $grade): int => $grade->score(),
            $this->competencyBreakdown,
        ));
        $competencyTotalPoints = array_sum(array_map(
            static fn (CompetencyGrade $grade): int => $grade->totalPoints(),
            $this->competencyBreakdown,
        ));

        if ($this->score !== $competencyScore) {
            throw new InvalidArgumentException('El puntaje total debe coincidir con el breakdown por competencia.');
        }

        if ($this->totalPoints !== $competencyTotalPoints) {
            throw new InvalidArgumentException('Los puntos totales deben coincidir con el breakdown por competencia.');
        }

        $expectedPercentage = (int) round($this->score / $this->totalPoints * 100);
        if ($this->percentage !== $expectedPercentage) {
            throw new InvalidArgumentException('El porcentaje total debe coincidir con el puntaje y los puntos totales.');
        }
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

    public function passed(): bool
    {
        return $this->passed;
    }

    /** @return list<AttemptQuestionGrade> */
    public function questionBreakdown(): array
    {
        return $this->questionBreakdown;
    }

    /** @return list<CompetencyGrade> */
    public function competencyBreakdown(): array
    {
        return $this->competencyBreakdown;
    }

    /**
     * @return array{
     *     score: int,
     *     total_points: int,
     *     percentage: int,
     *     passed: bool,
     *     question_breakdown: list<array<string, string|int|bool>>,
     *     competency_breakdown: list<array<string, string|int>>
     * }
     */
    public function toArray(): array
    {
        return [
            'score' => $this->score,
            'total_points' => $this->totalPoints,
            'percentage' => $this->percentage,
            'passed' => $this->passed,
            'question_breakdown' => array_map(
                static fn (AttemptQuestionGrade $grade): array => $grade->toArray(),
                $this->questionBreakdown,
            ),
            'competency_breakdown' => array_map(
                static fn (CompetencyGrade $grade): array => $grade->toArray(),
                $this->competencyBreakdown,
            ),
        ];
    }
}

<?php

declare(strict_types=1);

namespace Modules\Simulation\Domain\ValueObjects;

final readonly class DecisionEngineResult
{
    /** @param list<DecisionPointEvaluation> $evaluations */
    public function __construct(
        public string $sessionId,
        public array $evaluations,
        public int $appropriateCount,
        public int $inappropriateCount,
        public float $consistencyScore,
    ) {}
}

<?php

declare(strict_types=1);

namespace Modules\Simulation\Application\Responses;

use DateTimeInterface;
use Modules\Simulation\Domain\ValueObjects\DecisionEngineResult;
use Modules\Simulation\Domain\ValueObjects\DecisionPointEvaluation;

final readonly class DecisionEngineResultResponse
{
    /**
     * @param  list<array{road_context: string, risk_level: string, driver_reaction: string, outcome: string, feedback: string, occurred_at: string}>  $evaluations
     */
    public function __construct(
        public string $sessionId,
        public array $evaluations,
        public int $appropriateCount,
        public int $inappropriateCount,
        public float $consistencyScore,
    ) {}

    public static function fromDecisionEngineResult(DecisionEngineResult $result): self
    {
        return new self(
            sessionId: $result->sessionId,
            evaluations: array_map(
                static fn (DecisionPointEvaluation $evaluation): array => [
                    'road_context' => $evaluation->roadContext,
                    'risk_level' => $evaluation->riskLevel->value,
                    'driver_reaction' => $evaluation->driverReaction->value,
                    'outcome' => $evaluation->outcome->value,
                    'feedback' => $evaluation->feedback,
                    'occurred_at' => $evaluation->occurredAt->format(DateTimeInterface::ATOM),
                ],
                $result->evaluations,
            ),
            appropriateCount: $result->appropriateCount,
            inappropriateCount: $result->inappropriateCount,
            consistencyScore: $result->consistencyScore,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'session_id' => $this->sessionId,
            'evaluations' => $this->evaluations,
            'appropriate_count' => $this->appropriateCount,
            'inappropriate_count' => $this->inappropriateCount,
            'consistency_score' => $this->consistencyScore,
        ];
    }
}

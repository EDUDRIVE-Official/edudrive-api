<?php

declare(strict_types=1);

namespace Modules\Simulation\Application\Responses;

use DateTimeInterface;
use Modules\Simulation\Domain\ValueObjects\PracticalResult;
use Modules\Simulation\Domain\ValueObjects\PracticalResultError;

final readonly class PracticalResultResponse
{
    /**
     * @param  list<array{type: string, occurred_at: string, penalty_points: int, details: ?string}>  $errors
     * @param  list<string>  $competenciesDemonstrated
     * @param  list<string>  $recommendations
     */
    public function __construct(
        public string $sessionId,
        public string $outcome,
        public int $score,
        public int $totalPenaltyPoints,
        public array $errors,
        public array $competenciesDemonstrated,
        public array $recommendations,
    ) {}

    public static function fromPracticalResult(PracticalResult $result): self
    {
        return new self(
            sessionId: $result->sessionId,
            outcome: $result->outcome->value,
            score: $result->score,
            totalPenaltyPoints: $result->totalPenaltyPoints,
            errors: array_map(
                static fn (PracticalResultError $error): array => [
                    'type' => $error->type->value,
                    'occurred_at' => $error->occurredAt->format(DateTimeInterface::ATOM),
                    'penalty_points' => $error->penaltyPoints,
                    'details' => $error->details,
                ],
                $result->errors,
            ),
            competenciesDemonstrated: $result->competenciesDemonstrated,
            recommendations: $result->recommendations,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'session_id' => $this->sessionId,
            'outcome' => $this->outcome,
            'score' => $this->score,
            'total_penalty_points' => $this->totalPenaltyPoints,
            'errors' => $this->errors,
            'competencies_demonstrated' => $this->competenciesDemonstrated,
            'recommendations' => $this->recommendations,
        ];
    }
}

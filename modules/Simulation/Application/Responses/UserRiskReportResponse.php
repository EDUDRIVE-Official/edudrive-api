<?php

declare(strict_types=1);

namespace Modules\Simulation\Application\Responses;

final readonly class UserRiskReportResponse
{
    /** @param array<string, int> $inappropriateByRiskLevel */
    public function __construct(
        public string $userId,
        public int $totalDecisionPoints,
        public int $appropriateCount,
        public int $inappropriateCount,
        public ?float $averageConsistencyScore,
        public array $inappropriateByRiskLevel,
    ) {}

    /** @return array{user_id: string, total_decision_points: int, appropriate_count: int, inappropriate_count: int, average_consistency_score: float|null, inappropriate_by_risk_level: array<string, int>} */
    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'total_decision_points' => $this->totalDecisionPoints,
            'appropriate_count' => $this->appropriateCount,
            'inappropriate_count' => $this->inappropriateCount,
            'average_consistency_score' => $this->averageConsistencyScore,
            'inappropriate_by_risk_level' => $this->inappropriateByRiskLevel,
        ];
    }
}

<?php

declare(strict_types=1);

namespace Modules\Simulation\Application\UseCases;

use Modules\Simulation\Application\Queries\GetUserRiskReportQuery;
use Modules\Simulation\Application\Responses\UserRiskReportResponse;
use Modules\Simulation\Application\Services\ReportUserIdsResolver;
use Modules\Simulation\Domain\Aggregates\SimulationSession;
use Modules\Simulation\Domain\Enums\DecisionEvaluationOutcome;
use Modules\Simulation\Domain\Enums\DecisionRiskLevel;
use Modules\Simulation\Domain\Enums\SimulationSessionStatus;
use Modules\Simulation\Domain\Repositories\DecisionPointRepository;
use Modules\Simulation\Domain\Repositories\SimulationSessionRepository;
use Modules\Simulation\Domain\Services\DecisionEngineCalculator;

final readonly class GetUserRiskReportHandler
{
    public function __construct(
        private ReportUserIdsResolver $userIdsResolver,
        private SimulationSessionRepository $sessions,
        private DecisionPointRepository $decisionPoints,
    ) {}

    /** @return list<UserRiskReportResponse> */
    public function handle(GetUserRiskReportQuery $query): array
    {
        return array_map(
            fn (string $userId): UserRiskReportResponse => $this->reportFor($userId),
            $this->userIdsResolver->resolve($query->userIds),
        );
    }

    private function reportFor(string $userId): UserRiskReportResponse
    {
        $calculator = new DecisionEngineCalculator;

        $completedSessions = array_filter(
            $this->sessions->allForUser($userId),
            static fn (SimulationSession $session): bool => $session->status() === SimulationSessionStatus::Completed,
        );

        $appropriateCount = 0;
        $inappropriateCount = 0;
        $inappropriateByRiskLevel = [];
        foreach (DecisionRiskLevel::cases() as $riskLevel) {
            $inappropriateByRiskLevel[$riskLevel->value] = 0;
        }
        $consistencyScores = [];

        foreach ($completedSessions as $session) {
            $sessionId = $session->id()->value();
            $points = $this->decisionPoints->allForSession($sessionId);

            if ($points === []) {
                continue;
            }

            $result = $calculator->calculate($sessionId, $points);
            $appropriateCount += $result->appropriateCount;
            $inappropriateCount += $result->inappropriateCount;
            $consistencyScores[] = $result->consistencyScore;

            foreach ($result->evaluations as $evaluation) {
                if ($evaluation->outcome === DecisionEvaluationOutcome::Inappropriate) {
                    $inappropriateByRiskLevel[$evaluation->riskLevel->value]++;
                }
            }
        }

        return new UserRiskReportResponse(
            userId: $userId,
            totalDecisionPoints: $appropriateCount + $inappropriateCount,
            appropriateCount: $appropriateCount,
            inappropriateCount: $inappropriateCount,
            averageConsistencyScore: $consistencyScores === [] ? null : round(array_sum($consistencyScores) / count($consistencyScores), 2),
            inappropriateByRiskLevel: $inappropriateByRiskLevel,
        );
    }
}

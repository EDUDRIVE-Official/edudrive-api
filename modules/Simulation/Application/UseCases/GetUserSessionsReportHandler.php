<?php

declare(strict_types=1);

namespace Modules\Simulation\Application\UseCases;

use Modules\Simulation\Application\Queries\GetUserSessionsReportQuery;
use Modules\Simulation\Application\Responses\UserSessionsReportResponse;
use Modules\Simulation\Application\Services\ReportUserIdsResolver;
use Modules\Simulation\Domain\Aggregates\SimulationSession;
use Modules\Simulation\Domain\Enums\SimulationSessionStatus;
use Modules\Simulation\Domain\Repositories\SimulationSessionRepository;

final readonly class GetUserSessionsReportHandler
{
    public function __construct(
        private ReportUserIdsResolver $userIdsResolver,
        private SimulationSessionRepository $sessions,
    ) {}

    /** @return list<UserSessionsReportResponse> */
    public function handle(GetUserSessionsReportQuery $query): array
    {
        return array_map(
            fn (string $userId): UserSessionsReportResponse => $this->reportFor($userId),
            $this->userIdsResolver->resolve($query->userIds),
        );
    }

    private function reportFor(string $userId): UserSessionsReportResponse
    {
        $sessions = $this->sessions->allForUser($userId);

        $completedDurations = array_filter(array_map(
            static fn (SimulationSession $session): ?int => $session->actualDurationMinutes(),
            array_filter($sessions, static fn (SimulationSession $session): bool => $session->status() === SimulationSessionStatus::Completed),
        ), static fn (?int $duration): bool => $duration !== null);

        return new UserSessionsReportResponse(
            userId: $userId,
            sessionCount: count($sessions),
            completedCount: count(array_filter($sessions, static fn (SimulationSession $session): bool => $session->status() === SimulationSessionStatus::Completed)),
            cancelledCount: count(array_filter($sessions, static fn (SimulationSession $session): bool => $session->status() === SimulationSessionStatus::Cancelled)),
            averageDurationMinutes: $completedDurations === [] ? null : round(array_sum($completedDurations) / count($completedDurations), 2),
        );
    }
}

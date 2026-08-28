<?php

declare(strict_types=1);

namespace Modules\Simulation\Application\UseCases;

use Modules\Simulation\Application\Queries\GetUserTelemetryReportQuery;
use Modules\Simulation\Application\Responses\UserTelemetryReportResponse;
use Modules\Simulation\Application\Services\ReportUserIdsResolver;
use Modules\Simulation\Domain\Aggregates\SimulationSession;
use Modules\Simulation\Domain\Enums\SimulationSessionStatus;
use Modules\Simulation\Domain\Enums\TelemetryEventType;
use Modules\Simulation\Domain\Repositories\SimulationSessionRepository;
use Modules\Simulation\Domain\Repositories\TelemetryEventRepository;

final readonly class GetUserTelemetryReportHandler
{
    public function __construct(
        private ReportUserIdsResolver $userIdsResolver,
        private SimulationSessionRepository $sessions,
        private TelemetryEventRepository $events,
    ) {}

    /** @return list<UserTelemetryReportResponse> */
    public function handle(GetUserTelemetryReportQuery $query): array
    {
        return array_map(
            fn (string $userId): UserTelemetryReportResponse => $this->reportFor($userId),
            $this->userIdsResolver->resolve($query->userIds),
        );
    }

    private function reportFor(string $userId): UserTelemetryReportResponse
    {
        $completedSessions = array_filter(
            $this->sessions->allForUser($userId),
            static fn (SimulationSession $session): bool => $session->status() === SimulationSessionStatus::Completed,
        );

        $counts = [];
        foreach (TelemetryEventType::cases() as $type) {
            $counts[$type->value] = 0;
        }

        $totalEvents = 0;
        foreach ($completedSessions as $session) {
            foreach ($this->events->allForSession($session->id()->value()) as $event) {
                $counts[$event->type()->value]++;
                $totalEvents++;
            }
        }

        return new UserTelemetryReportResponse(
            userId: $userId,
            sessionCount: count($completedSessions),
            totalEvents: $totalEvents,
            eventCountsByType: $counts,
        );
    }
}

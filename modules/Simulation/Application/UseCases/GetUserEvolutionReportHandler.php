<?php

declare(strict_types=1);

namespace Modules\Simulation\Application\UseCases;

use DateTimeInterface;
use Modules\Simulation\Application\Queries\GetUserEvolutionReportQuery;
use Modules\Simulation\Application\Responses\UserEvolutionReportResponse;
use Modules\Simulation\Application\Services\ReportUserIdsResolver;
use Modules\Simulation\Domain\Aggregates\SimulationSession;
use Modules\Simulation\Domain\Enums\SimulationSessionStatus;
use Modules\Simulation\Domain\Repositories\SimulationSessionRepository;
use Modules\Simulation\Domain\Repositories\TelemetryEventRepository;
use Modules\Simulation\Domain\Services\PracticalResultCalculator;

final readonly class GetUserEvolutionReportHandler
{
    public function __construct(
        private ReportUserIdsResolver $userIdsResolver,
        private SimulationSessionRepository $sessions,
        private TelemetryEventRepository $events,
    ) {}

    /** @return list<UserEvolutionReportResponse> */
    public function handle(GetUserEvolutionReportQuery $query): array
    {
        return array_map(
            fn (string $userId): UserEvolutionReportResponse => $this->reportFor($userId),
            $this->userIdsResolver->resolve($query->userIds),
        );
    }

    private function reportFor(string $userId): UserEvolutionReportResponse
    {
        $calculator = new PracticalResultCalculator;

        $completedSessions = array_filter(
            $this->sessions->allForUser($userId),
            static fn (SimulationSession $session): bool => $session->status() === SimulationSessionStatus::Completed,
        );

        $entries = array_map(function (SimulationSession $session) use ($calculator): array {
            $result = $calculator->calculate($session, $this->events->allForSession($session->id()->value()));

            return [
                'session_id' => $session->id()->value(),
                'scenario' => $session->scenario(),
                'scheduled_at' => $session->scheduledAt()->format(DateTimeInterface::ATOM),
                'score' => $result->score,
                'outcome' => $result->outcome->value,
            ];
        }, $completedSessions);

        return new UserEvolutionReportResponse(
            userId: $userId,
            entries: array_values($entries),
        );
    }
}

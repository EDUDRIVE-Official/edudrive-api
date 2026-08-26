<?php

declare(strict_types=1);

namespace Modules\Simulation\Application\UseCases;

use DateTimeImmutable;
use Modules\Simulation\Application\Commands\SubmitDecisionPointsCommand;
use Modules\Simulation\Application\Exceptions\SimulationSessionNotFound;
use Modules\Simulation\Application\Exceptions\SimulationSessionNotInProgress;
use Modules\Simulation\Application\Responses\DecisionPointsBatchResponse;
use Modules\Simulation\Domain\Entities\DecisionPoint;
use Modules\Simulation\Domain\Enums\DecisionRiskLevel;
use Modules\Simulation\Domain\Enums\DriverReactionType;
use Modules\Simulation\Domain\Repositories\DecisionPointRepository;
use Modules\Simulation\Domain\Repositories\SimulationSessionRepository;
use Modules\Simulation\Domain\ValueObjects\SimulationSessionId;

final readonly class SubmitDecisionPointsHandler
{
    public function __construct(
        private SimulationSessionRepository $sessions,
        private DecisionPointRepository $decisionPoints,
    ) {}

    public function handle(SubmitDecisionPointsCommand $command): DecisionPointsBatchResponse
    {
        $session = $this->sessions->findById(SimulationSessionId::fromString($command->sessionId));
        if ($session === null || $session->simulatorId() !== $command->simulatorId) {
            throw SimulationSessionNotFound::withId($command->sessionId);
        }

        if ($session->startedAt() === null) {
            throw SimulationSessionNotInProgress::create();
        }

        $points = array_map(
            fn (array $data): DecisionPoint => DecisionPoint::record(
                id: (string) $data['id'],
                sessionId: $command->sessionId,
                roadContext: (string) $data['road_context'],
                riskLevel: DecisionRiskLevel::from((string) $data['risk_level']),
                driverReaction: DriverReactionType::from((string) $data['driver_reaction']),
                occurredAt: new DateTimeImmutable((string) $data['occurred_at']),
            ),
            $command->decisions,
        );

        foreach ($points as $point) {
            if (! $session->wasInProgressAt($point->occurredAt())) {
                throw SimulationSessionNotInProgress::create();
            }
        }

        return new DecisionPointsBatchResponse($this->decisionPoints->saveBatch($points));
    }
}

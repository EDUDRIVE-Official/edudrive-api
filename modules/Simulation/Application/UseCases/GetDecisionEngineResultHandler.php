<?php

declare(strict_types=1);

namespace Modules\Simulation\Application\UseCases;

use Modules\Simulation\Application\Exceptions\DecisionEngineResultNotAvailable;
use Modules\Simulation\Application\Exceptions\SimulationSessionNotFound;
use Modules\Simulation\Application\Queries\GetDecisionEngineResultQuery;
use Modules\Simulation\Application\Responses\DecisionEngineResultResponse;
use Modules\Simulation\Domain\Enums\SimulationSessionStatus;
use Modules\Simulation\Domain\Repositories\DecisionPointRepository;
use Modules\Simulation\Domain\Repositories\SimulationSessionRepository;
use Modules\Simulation\Domain\Services\DecisionEngineCalculator;
use Modules\Simulation\Domain\ValueObjects\SimulationSessionId;

final readonly class GetDecisionEngineResultHandler
{
    public function __construct(
        private SimulationSessionRepository $sessions,
        private DecisionPointRepository $decisionPoints,
    ) {}

    public function handle(GetDecisionEngineResultQuery $query): DecisionEngineResultResponse
    {
        $session = $this->sessions->findById(SimulationSessionId::fromString($query->sessionId));
        if ($session === null) {
            throw SimulationSessionNotFound::withId($query->sessionId);
        }

        if ($session->userId() !== $query->userId && ! $query->canViewOthers) {
            throw SimulationSessionNotFound::withId($query->sessionId);
        }

        if ($session->status() !== SimulationSessionStatus::Completed) {
            throw DecisionEngineResultNotAvailable::create();
        }

        $result = (new DecisionEngineCalculator)->calculate(
            $query->sessionId,
            $this->decisionPoints->allForSession($query->sessionId),
        );

        return DecisionEngineResultResponse::fromDecisionEngineResult($result);
    }
}

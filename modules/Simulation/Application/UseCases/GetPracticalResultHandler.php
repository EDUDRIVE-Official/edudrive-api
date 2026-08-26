<?php

declare(strict_types=1);

namespace Modules\Simulation\Application\UseCases;

use Modules\Simulation\Application\Exceptions\PracticalResultNotAvailable;
use Modules\Simulation\Application\Exceptions\SimulationSessionNotFound;
use Modules\Simulation\Application\Queries\GetPracticalResultQuery;
use Modules\Simulation\Application\Responses\PracticalResultResponse;
use Modules\Simulation\Domain\Enums\SimulationSessionStatus;
use Modules\Simulation\Domain\Repositories\SimulationSessionRepository;
use Modules\Simulation\Domain\Repositories\TelemetryEventRepository;
use Modules\Simulation\Domain\Services\PracticalResultCalculator;
use Modules\Simulation\Domain\ValueObjects\SimulationSessionId;

final readonly class GetPracticalResultHandler
{
    public function __construct(
        private SimulationSessionRepository $sessions,
        private TelemetryEventRepository $events,
    ) {}

    public function handle(GetPracticalResultQuery $query): PracticalResultResponse
    {
        $session = $this->sessions->findById(SimulationSessionId::fromString($query->sessionId));
        if ($session === null) {
            throw SimulationSessionNotFound::withId($query->sessionId);
        }

        if ($session->userId() !== $query->userId && ! $query->canViewOthers) {
            throw SimulationSessionNotFound::withId($query->sessionId);
        }

        if ($session->status() !== SimulationSessionStatus::Completed) {
            throw PracticalResultNotAvailable::create();
        }

        $result = (new PracticalResultCalculator)->calculate(
            $session,
            $this->events->allForSession($query->sessionId),
        );

        return PracticalResultResponse::fromPracticalResult($result);
    }
}

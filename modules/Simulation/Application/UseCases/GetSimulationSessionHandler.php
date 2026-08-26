<?php

declare(strict_types=1);

namespace Modules\Simulation\Application\UseCases;

use Modules\Simulation\Application\Exceptions\SimulationSessionNotFound;
use Modules\Simulation\Application\Queries\GetSimulationSessionQuery;
use Modules\Simulation\Application\Responses\SimulationSessionResponse;
use Modules\Simulation\Domain\Repositories\SimulationSessionRepository;
use Modules\Simulation\Domain\ValueObjects\SimulationSessionId;

final readonly class GetSimulationSessionHandler
{
    public function __construct(private SimulationSessionRepository $sessions) {}

    public function handle(GetSimulationSessionQuery $query): SimulationSessionResponse
    {
        $session = $this->sessions->findById(SimulationSessionId::fromString($query->sessionId));
        if ($session === null) {
            throw SimulationSessionNotFound::withId($query->sessionId);
        }

        if ($session->userId() !== $query->userId && ! $query->canViewOthers) {
            throw SimulationSessionNotFound::withId($query->sessionId);
        }

        return SimulationSessionResponse::fromSimulationSession($session);
    }
}

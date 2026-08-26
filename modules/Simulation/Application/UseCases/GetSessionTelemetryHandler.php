<?php

declare(strict_types=1);

namespace Modules\Simulation\Application\UseCases;

use Modules\Simulation\Application\Exceptions\SimulationSessionNotFound;
use Modules\Simulation\Application\Queries\GetSessionTelemetryQuery;
use Modules\Simulation\Application\Responses\TelemetryResponse;
use Modules\Simulation\Domain\Repositories\SimulationSessionRepository;
use Modules\Simulation\Domain\Repositories\TelemetryEventRepository;
use Modules\Simulation\Domain\Repositories\TelemetrySampleRepository;
use Modules\Simulation\Domain\ValueObjects\SimulationSessionId;

final readonly class GetSessionTelemetryHandler
{
    public function __construct(
        private SimulationSessionRepository $sessions,
        private TelemetrySampleRepository $samples,
        private TelemetryEventRepository $events,
    ) {}

    public function handle(GetSessionTelemetryQuery $query): TelemetryResponse
    {
        $session = $this->sessions->findById(SimulationSessionId::fromString($query->sessionId));
        if ($session === null) {
            throw SimulationSessionNotFound::withId($query->sessionId);
        }

        if ($session->userId() !== $query->userId && ! $query->canViewOthers) {
            throw SimulationSessionNotFound::withId($query->sessionId);
        }

        return TelemetryResponse::fromSamplesAndEvents(
            sessionId: $query->sessionId,
            samples: $this->samples->allForSession($query->sessionId),
            events: $this->events->allForSession($query->sessionId),
        );
    }
}

<?php

declare(strict_types=1);

namespace Modules\Simulation\Application\UseCases;

use Modules\Simulation\Application\Queries\ListSimulationSessionsQuery;
use Modules\Simulation\Application\Responses\SimulationSessionResponse;
use Modules\Simulation\Domain\Aggregates\SimulationSession;
use Modules\Simulation\Domain\Repositories\SimulationSessionRepository;

final readonly class ListSimulationSessionsHandler
{
    public function __construct(private SimulationSessionRepository $sessions) {}

    /** @return list<SimulationSessionResponse> */
    public function handle(ListSimulationSessionsQuery $query): array
    {
        return array_map(
            static fn (SimulationSession $session): SimulationSessionResponse => SimulationSessionResponse::fromSimulationSession($session),
            $this->sessions->all(),
        );
    }
}

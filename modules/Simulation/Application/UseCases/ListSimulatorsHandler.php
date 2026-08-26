<?php

declare(strict_types=1);

namespace Modules\Simulation\Application\UseCases;

use Modules\Simulation\Application\Queries\ListSimulatorsQuery;
use Modules\Simulation\Application\Responses\SimulatorResponse;
use Modules\Simulation\Domain\Aggregates\Simulator;
use Modules\Simulation\Domain\Repositories\SimulatorRepository;

final readonly class ListSimulatorsHandler
{
    public function __construct(private SimulatorRepository $simulators) {}

    /** @return list<SimulatorResponse> */
    public function handle(ListSimulatorsQuery $query): array
    {
        return array_map(
            static fn (Simulator $simulator): SimulatorResponse => SimulatorResponse::fromSimulator($simulator),
            $this->simulators->all(),
        );
    }
}

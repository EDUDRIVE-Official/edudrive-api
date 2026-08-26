<?php

declare(strict_types=1);

namespace Modules\Simulation\Application\UseCases;

use Modules\Simulation\Application\Exceptions\SimulatorNotFound;
use Modules\Simulation\Application\Queries\GetSimulatorQuery;
use Modules\Simulation\Application\Responses\SimulatorResponse;
use Modules\Simulation\Domain\Repositories\SimulatorRepository;
use Modules\Simulation\Domain\ValueObjects\SimulatorId;

final readonly class GetSimulatorHandler
{
    public function __construct(private SimulatorRepository $simulators) {}

    public function handle(GetSimulatorQuery $query): SimulatorResponse
    {
        $simulator = $this->simulators->findById(SimulatorId::fromString($query->simulatorId));
        if ($simulator === null) {
            throw SimulatorNotFound::withId($query->simulatorId);
        }

        return SimulatorResponse::fromSimulator($simulator);
    }
}

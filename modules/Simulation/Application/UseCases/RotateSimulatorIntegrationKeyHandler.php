<?php

declare(strict_types=1);

namespace Modules\Simulation\Application\UseCases;

use Modules\Simulation\Application\Commands\RotateSimulatorIntegrationKeyCommand;
use Modules\Simulation\Application\Exceptions\SimulatorNotFound;
use Modules\Simulation\Application\Responses\SimulatorResponse;
use Modules\Simulation\Domain\Repositories\SimulatorRepository;
use Modules\Simulation\Domain\ValueObjects\IntegrationKey;
use Modules\Simulation\Domain\ValueObjects\SimulatorId;

final readonly class RotateSimulatorIntegrationKeyHandler
{
    public function __construct(private SimulatorRepository $simulators) {}

    public function handle(RotateSimulatorIntegrationKeyCommand $command): SimulatorResponse
    {
        $simulator = $this->simulators->findById(SimulatorId::fromString($command->simulatorId));
        if ($simulator === null) {
            throw SimulatorNotFound::withId($command->simulatorId);
        }

        $newKey = IntegrationKey::generate();
        $simulator->rotateIntegrationKey($newKey);
        $this->simulators->save($simulator);

        return SimulatorResponse::fromSimulator($simulator, $newKey->plainValue());
    }
}

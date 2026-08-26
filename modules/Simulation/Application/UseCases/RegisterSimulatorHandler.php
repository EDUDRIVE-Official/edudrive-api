<?php

declare(strict_types=1);

namespace Modules\Simulation\Application\UseCases;

use Illuminate\Support\Str;
use Modules\Simulation\Application\Commands\RegisterSimulatorCommand;
use Modules\Simulation\Application\Exceptions\SimulatorAlreadyExists;
use Modules\Simulation\Application\Responses\SimulatorResponse;
use Modules\Simulation\Domain\Aggregates\Simulator;
use Modules\Simulation\Domain\Repositories\SimulatorRepository;
use Modules\Simulation\Domain\ValueObjects\DeviceIdentifier;
use Modules\Simulation\Domain\ValueObjects\IntegrationKey;
use Modules\Simulation\Domain\ValueObjects\SimulatorId;

final readonly class RegisterSimulatorHandler
{
    public function __construct(private SimulatorRepository $simulators) {}

    public function handle(RegisterSimulatorCommand $command): SimulatorResponse
    {
        $deviceIdentifier = DeviceIdentifier::fromString($command->deviceIdentifier);

        if ($this->simulators->findByDeviceIdentifier($deviceIdentifier) !== null) {
            throw SimulatorAlreadyExists::create();
        }

        $integrationKey = IntegrationKey::generate();
        $simulator = Simulator::register(
            id: SimulatorId::fromString((string) Str::uuid()),
            deviceIdentifier: $deviceIdentifier,
            softwareVersion: $command->softwareVersion,
            location: $command->location,
            integrationKey: $integrationKey,
        );

        $this->simulators->save($simulator);

        return SimulatorResponse::fromSimulator($simulator, $integrationKey->plainValue());
    }
}

<?php

declare(strict_types=1);

namespace Modules\Simulation\Domain\Repositories;

use Modules\Simulation\Domain\Aggregates\Simulator;
use Modules\Simulation\Domain\ValueObjects\DeviceIdentifier;
use Modules\Simulation\Domain\ValueObjects\SimulatorId;

interface SimulatorRepository
{
    public function save(Simulator $simulator): void;

    public function findById(SimulatorId $id): ?Simulator;

    public function findByDeviceIdentifier(DeviceIdentifier $deviceIdentifier): ?Simulator;

    public function findByIntegrationKeyHash(string $integrationKeyHash): ?Simulator;

    /** @return list<Simulator> */
    public function all(): array;
}

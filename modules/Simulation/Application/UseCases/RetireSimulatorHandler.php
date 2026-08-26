<?php

declare(strict_types=1);

namespace Modules\Simulation\Application\UseCases;

use DateTimeImmutable;
use Modules\Simulation\Application\Commands\RetireSimulatorCommand;
use Modules\Simulation\Application\Exceptions\SimulatorNotFound;
use Modules\Simulation\Application\Responses\SimulatorResponse;
use Modules\Simulation\Domain\Repositories\SimulatorRepository;
use Modules\Simulation\Domain\ValueObjects\SimulatorId;

final readonly class RetireSimulatorHandler
{
    public function __construct(private SimulatorRepository $simulators) {}

    public function handle(RetireSimulatorCommand $command): SimulatorResponse
    {
        $simulator = $this->simulators->findById(SimulatorId::fromString($command->simulatorId));
        if ($simulator === null) {
            throw SimulatorNotFound::withId($command->simulatorId);
        }

        $simulator->retire($command->reason, new DateTimeImmutable('now'));
        $this->simulators->save($simulator);

        return SimulatorResponse::fromSimulator($simulator);
    }
}

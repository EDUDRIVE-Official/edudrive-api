<?php

declare(strict_types=1);

namespace Modules\Simulation\Application\UseCases;

use Illuminate\Support\Str;
use Modules\Simulation\Application\Commands\ScheduleSimulationSessionCommand;
use Modules\Simulation\Application\Exceptions\SimulatorNotAvailable;
use Modules\Simulation\Application\Exceptions\SimulatorNotFound;
use Modules\Simulation\Application\Responses\SimulationSessionResponse;
use Modules\Simulation\Domain\Aggregates\SimulationSession;
use Modules\Simulation\Domain\Enums\SimulatorStatus;
use Modules\Simulation\Domain\Repositories\SimulationSessionRepository;
use Modules\Simulation\Domain\Repositories\SimulatorRepository;
use Modules\Simulation\Domain\ValueObjects\SimulationSessionId;
use Modules\Simulation\Domain\ValueObjects\SimulatorId;

final readonly class ScheduleSimulationSessionHandler
{
    public function __construct(
        private SimulationSessionRepository $sessions,
        private SimulatorRepository $simulators,
    ) {}

    public function handle(ScheduleSimulationSessionCommand $command): SimulationSessionResponse
    {
        $simulator = $this->simulators->findById(SimulatorId::fromString($command->simulatorId));
        if ($simulator === null) {
            throw SimulatorNotFound::withId($command->simulatorId);
        }

        if ($simulator->status() !== SimulatorStatus::Active) {
            throw SimulatorNotAvailable::create();
        }

        $session = SimulationSession::schedule(
            id: SimulationSessionId::fromString((string) Str::uuid()),
            userId: $command->userId,
            simulatorId: $command->simulatorId,
            vehicleType: $command->vehicleType,
            scenario: $command->scenario,
            scheduledAt: $command->scheduledAt,
            plannedDurationMinutes: $command->plannedDurationMinutes,
        );

        $this->sessions->save($session);

        return SimulationSessionResponse::fromSimulationSession($session);
    }
}

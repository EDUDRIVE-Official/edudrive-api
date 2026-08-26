<?php

declare(strict_types=1);

namespace Modules\Simulation\Application\Commands;

use DateTimeImmutable;
use Modules\Foundation\Application\Commands\Command;

final readonly class ScheduleSimulationSessionCommand implements Command
{
    public function __construct(
        public string $userId,
        public string $simulatorId,
        public string $vehicleType,
        public string $scenario,
        public DateTimeImmutable $scheduledAt,
        public int $plannedDurationMinutes,
    ) {}
}

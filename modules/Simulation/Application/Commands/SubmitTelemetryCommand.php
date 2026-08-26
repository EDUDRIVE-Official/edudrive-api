<?php

declare(strict_types=1);

namespace Modules\Simulation\Application\Commands;

use Modules\Foundation\Application\Commands\Command;

final readonly class SubmitTelemetryCommand implements Command
{
    /**
     * @param  list<array{id: string, speed_kph: float, braking_percentage: float, acceleration_mps2: float, steering_angle_degrees: float, recorded_at: string}>  $samples
     * @param  list<array{id: string, type: string, details: ?string, occurred_at: string}>  $events
     */
    public function __construct(
        public string $sessionId,
        public string $simulatorId,
        public array $samples,
        public array $events,
    ) {}
}

<?php

declare(strict_types=1);

namespace Modules\Simulation\Domain\Enums;

enum TelemetryEventType: string
{
    case Collision = 'collision';
    case Infraction = 'infraction';
    case SignalUsage = 'signal_usage';
    case Critical = 'critical';
}

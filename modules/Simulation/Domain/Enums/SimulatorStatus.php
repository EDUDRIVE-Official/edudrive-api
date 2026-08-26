<?php

declare(strict_types=1);

namespace Modules\Simulation\Domain\Enums;

enum SimulatorStatus: string
{
    case Active = 'active';
    case Suspended = 'suspended';
    case Retired = 'retired';
}

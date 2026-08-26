<?php

declare(strict_types=1);

namespace Modules\Simulation\Domain\Enums;

enum DriverReactionType: string
{
    case Braked = 'braked';
    case Accelerated = 'accelerated';
    case Maintained = 'maintained';
    case Swerved = 'swerved';
    case Signaled = 'signaled';
    case Ignored = 'ignored';
}

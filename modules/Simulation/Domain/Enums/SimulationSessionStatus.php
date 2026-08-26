<?php

declare(strict_types=1);

namespace Modules\Simulation\Domain\Enums;

enum SimulationSessionStatus: string
{
    case Scheduled = 'scheduled';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
}

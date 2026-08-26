<?php

declare(strict_types=1);

namespace Modules\Simulation\Domain\Enums;

enum DecisionRiskLevel: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
}

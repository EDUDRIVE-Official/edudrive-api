<?php

declare(strict_types=1);

namespace Modules\AiGovernance\Domain\Enums;

enum AiIncidentSeverity: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
    case Critical = 'critical';
}

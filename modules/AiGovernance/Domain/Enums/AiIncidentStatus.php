<?php

declare(strict_types=1);

namespace Modules\AiGovernance\Domain\Enums;

enum AiIncidentStatus: string
{
    case Open = 'open';
    case Investigating = 'investigating';
    case Resolved = 'resolved';
}

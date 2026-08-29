<?php

declare(strict_types=1);

namespace Modules\AiGovernance\Domain\Enums;

enum AiSystemStatus: string
{
    case Evaluation = 'evaluation';
    case Pilot = 'pilot';
    case Production = 'production';
    case Suspended = 'suspended';
    case Retired = 'retired';
}

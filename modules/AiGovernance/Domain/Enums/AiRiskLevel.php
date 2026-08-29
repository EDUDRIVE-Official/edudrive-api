<?php

declare(strict_types=1);

namespace Modules\AiGovernance\Domain\Enums;

enum AiRiskLevel: string
{
    case Ia0 = 'ia0';
    case Ia1 = 'ia1';
    case Ia2 = 'ia2';
    case Ia3 = 'ia3';
    case Ia4 = 'ia4';
}

<?php

declare(strict_types=1);

namespace Modules\AiGovernance\Domain\Enums;

enum AiSupervisionLevel: int
{
    case Informs = 1;
    case Recommends = 2;
    case Proposes = 3;
    case Automates = 4;
}

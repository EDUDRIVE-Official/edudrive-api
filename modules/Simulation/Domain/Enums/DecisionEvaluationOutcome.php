<?php

declare(strict_types=1);

namespace Modules\Simulation\Domain\Enums;

enum DecisionEvaluationOutcome: string
{
    case Appropriate = 'appropriate';
    case Inappropriate = 'inappropriate';
}

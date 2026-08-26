<?php

declare(strict_types=1);

namespace Modules\Simulation\Domain\Enums;

enum PracticalResultOutcome: string
{
    case Passed = 'passed';
    case Failed = 'failed';
}

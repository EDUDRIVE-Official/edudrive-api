<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\Enums;

enum MasteryLevel: string
{
    case Foundation = 'foundation';
    case Developing = 'developing';
    case Proficient = 'proficient';
    case Advanced = 'advanced';
}

<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\Enums;

enum LicenseStage: string
{
    case Unlicensed = 'unlicensed';
    case Learner = 'learner';
    case Licensed = 'licensed';
    case Professional = 'professional';
}

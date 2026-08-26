<?php

declare(strict_types=1);

namespace Modules\RoadPassport\Domain\Enums;

enum RoadPassportStatus: string
{
    case Active = 'active';
    case Suspended = 'suspended';
    case Revoked = 'revoked';
}

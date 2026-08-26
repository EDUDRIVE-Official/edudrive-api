<?php

declare(strict_types=1);

namespace Modules\Gamification\Domain\Enums;

enum BadgeStatus: string
{
    case Active = 'active';
    case Retired = 'retired';
}

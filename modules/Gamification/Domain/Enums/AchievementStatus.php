<?php

declare(strict_types=1);

namespace Modules\Gamification\Domain\Enums;

enum AchievementStatus: string
{
    case Active = 'active';
    case Retired = 'retired';
}

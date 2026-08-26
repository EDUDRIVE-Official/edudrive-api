<?php

declare(strict_types=1);

namespace Modules\Gamification\Domain\Enums;

enum BadgeLevel: string
{
    case Bronze = 'bronze';
    case Silver = 'silver';
    case Gold = 'gold';
}

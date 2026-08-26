<?php

declare(strict_types=1);

namespace Modules\Gamification\Domain\Enums;

enum BadgeCategory: string
{
    case Educational = 'educational';
    case Institutional = 'institutional';
    case Practical = 'practical';
}

<?php

declare(strict_types=1);

namespace Modules\Gamification\Domain\Enums;

enum ChallengeType: string
{
    case Individual = 'individual';
    case Group = 'group';
    case Educational = 'educational';
}

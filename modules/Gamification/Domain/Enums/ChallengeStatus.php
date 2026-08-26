<?php

declare(strict_types=1);

namespace Modules\Gamification\Domain\Enums;

enum ChallengeStatus: string
{
    case Active = 'active';
    case Retired = 'retired';
}

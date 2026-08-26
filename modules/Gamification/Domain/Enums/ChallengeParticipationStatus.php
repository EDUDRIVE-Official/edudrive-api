<?php

declare(strict_types=1);

namespace Modules\Gamification\Domain\Enums;

enum ChallengeParticipationStatus: string
{
    case Joined = 'joined';
    case Completed = 'completed';
}

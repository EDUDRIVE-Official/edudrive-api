<?php

declare(strict_types=1);

namespace Modules\Gamification\Domain\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class InvalidChallengeParticipationTransition extends DomainException
{
    public static function create(): self
    {
        return new self(
            message: 'La participacion ya se encuentra completada.',
            errorCode: 'INVALID_CHALLENGE_PARTICIPATION_TRANSITION',
            statusCode: 422,
        );
    }
}

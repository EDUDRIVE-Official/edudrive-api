<?php

declare(strict_types=1);

namespace Modules\Gamification\Application\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class ChallengeParticipationNotFound extends DomainException
{
    public static function create(): self
    {
        return new self(
            message: 'El usuario no esta registrado en este reto.',
            errorCode: 'CHALLENGE_PARTICIPATION_NOT_FOUND',
            statusCode: 404,
        );
    }
}

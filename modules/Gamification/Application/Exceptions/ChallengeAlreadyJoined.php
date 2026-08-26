<?php

declare(strict_types=1);

namespace Modules\Gamification\Application\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class ChallengeAlreadyJoined extends DomainException
{
    public static function create(): self
    {
        return new self(
            message: 'El usuario ya esta registrado en este reto.',
            errorCode: 'CHALLENGE_ALREADY_JOINED',
            statusCode: 409,
        );
    }
}

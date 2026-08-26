<?php

declare(strict_types=1);

namespace Modules\Gamification\Application\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class ChallengeAlreadyExists extends DomainException
{
    public static function create(): self
    {
        return new self(
            message: 'Ya existe un reto registrado con ese codigo.',
            errorCode: 'CHALLENGE_ALREADY_EXISTS',
            statusCode: 409,
        );
    }
}

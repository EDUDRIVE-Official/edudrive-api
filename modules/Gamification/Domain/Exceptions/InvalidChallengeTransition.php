<?php

declare(strict_types=1);

namespace Modules\Gamification\Domain\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class InvalidChallengeTransition extends DomainException
{
    public static function create(): self
    {
        return new self(
            message: 'El reto ya se encuentra retirado.',
            errorCode: 'INVALID_CHALLENGE_TRANSITION',
            statusCode: 422,
        );
    }
}

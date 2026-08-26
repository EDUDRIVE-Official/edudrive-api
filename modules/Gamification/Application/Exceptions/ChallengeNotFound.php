<?php

declare(strict_types=1);

namespace Modules\Gamification\Application\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class ChallengeNotFound extends DomainException
{
    public static function withId(string $challengeId): self
    {
        return new self(
            message: "No se encontro el reto {$challengeId}.",
            errorCode: 'CHALLENGE_NOT_FOUND',
            statusCode: 404,
        );
    }
}

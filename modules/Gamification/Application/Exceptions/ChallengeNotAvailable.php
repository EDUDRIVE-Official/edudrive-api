<?php

declare(strict_types=1);

namespace Modules\Gamification\Application\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class ChallengeNotAvailable extends DomainException
{
    public static function create(): self
    {
        return new self(
            message: 'El reto esta retirado o fuera de su ventana de vigencia.',
            errorCode: 'CHALLENGE_NOT_AVAILABLE',
            statusCode: 422,
        );
    }
}

<?php

declare(strict_types=1);

namespace Modules\Gamification\Application\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class BadgeAlreadyGranted extends DomainException
{
    public static function create(): self
    {
        return new self(
            message: 'El usuario ya tiene esta insignia otorgada.',
            errorCode: 'BADGE_ALREADY_GRANTED',
            statusCode: 409,
        );
    }
}

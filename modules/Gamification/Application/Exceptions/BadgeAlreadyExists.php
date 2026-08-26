<?php

declare(strict_types=1);

namespace Modules\Gamification\Application\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class BadgeAlreadyExists extends DomainException
{
    public static function create(): self
    {
        return new self(
            message: 'Ya existe una insignia registrada con ese codigo.',
            errorCode: 'BADGE_ALREADY_EXISTS',
            statusCode: 409,
        );
    }
}

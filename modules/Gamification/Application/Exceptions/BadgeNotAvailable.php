<?php

declare(strict_types=1);

namespace Modules\Gamification\Application\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class BadgeNotAvailable extends DomainException
{
    public static function create(): self
    {
        return new self(
            message: 'La insignia esta retirada y no puede otorgarse.',
            errorCode: 'BADGE_NOT_AVAILABLE',
            statusCode: 422,
        );
    }
}

<?php

declare(strict_types=1);

namespace Modules\Gamification\Application\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class BadgeNotFound extends DomainException
{
    public static function withId(string $badgeId): self
    {
        return new self(
            message: "No se encontro la insignia {$badgeId}.",
            errorCode: 'BADGE_NOT_FOUND',
            statusCode: 404,
        );
    }
}

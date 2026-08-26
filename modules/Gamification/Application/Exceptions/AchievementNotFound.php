<?php

declare(strict_types=1);

namespace Modules\Gamification\Application\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class AchievementNotFound extends DomainException
{
    public static function withId(string $achievementId): self
    {
        return new self(
            message: "No se encontro el logro {$achievementId}.",
            errorCode: 'ACHIEVEMENT_NOT_FOUND',
            statusCode: 404,
        );
    }
}

<?php

declare(strict_types=1);

namespace Modules\Gamification\Application\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class AchievementAlreadyGranted extends DomainException
{
    public static function create(): self
    {
        return new self(
            message: 'El usuario ya tiene este logro otorgado.',
            errorCode: 'ACHIEVEMENT_ALREADY_GRANTED',
            statusCode: 409,
        );
    }
}

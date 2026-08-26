<?php

declare(strict_types=1);

namespace Modules\Gamification\Domain\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class InvalidAchievementTransition extends DomainException
{
    public static function create(): self
    {
        return new self(
            message: 'La transicion de estado del logro no es valida.',
            errorCode: 'INVALID_ACHIEVEMENT_TRANSITION',
            statusCode: 422,
        );
    }
}

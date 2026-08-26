<?php

declare(strict_types=1);

namespace Modules\Gamification\Application\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class AchievementAlreadyExists extends DomainException
{
    public static function create(): self
    {
        return new self(
            message: 'Ya existe un logro registrado con ese codigo.',
            errorCode: 'ACHIEVEMENT_ALREADY_EXISTS',
            statusCode: 409,
        );
    }
}

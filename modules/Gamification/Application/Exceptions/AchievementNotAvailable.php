<?php

declare(strict_types=1);

namespace Modules\Gamification\Application\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class AchievementNotAvailable extends DomainException
{
    public static function create(): self
    {
        return new self(
            message: 'El logro esta retirado y no puede otorgarse.',
            errorCode: 'ACHIEVEMENT_NOT_AVAILABLE',
            statusCode: 422,
        );
    }
}

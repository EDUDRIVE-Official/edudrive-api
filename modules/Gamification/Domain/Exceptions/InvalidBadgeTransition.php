<?php

declare(strict_types=1);

namespace Modules\Gamification\Domain\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class InvalidBadgeTransition extends DomainException
{
    public static function alreadyRetired(): self
    {
        return new self(
            message: 'La insignia ya se encuentra retirada.',
            errorCode: 'INVALID_BADGE_TRANSITION',
            statusCode: 422,
        );
    }

    public static function cannotEditRetired(): self
    {
        return new self(
            message: 'No se puede editar el contenido de una insignia retirada.',
            errorCode: 'INVALID_BADGE_TRANSITION',
            statusCode: 422,
        );
    }
}

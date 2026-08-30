<?php

declare(strict_types=1);

namespace Modules\Identity\Domain\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class InvalidGuardianRelationship extends DomainException
{
    public static function selfGuardianship(): self
    {
        return new self(
            message: 'Un usuario no puede ser su propio tutor.',
            errorCode: 'INVALID_GUARDIAN_RELATIONSHIP',
            statusCode: 422,
        );
    }

    public static function alreadyRevoked(): self
    {
        return new self(
            message: 'Esta relación ya fue revocada.',
            errorCode: 'INVALID_GUARDIAN_RELATIONSHIP',
            statusCode: 422,
        );
    }
}

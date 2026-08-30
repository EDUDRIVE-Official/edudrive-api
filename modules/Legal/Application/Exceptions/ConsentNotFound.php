<?php

declare(strict_types=1);

namespace Modules\Legal\Application\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class ConsentNotFound extends DomainException
{
    public static function create(): self
    {
        return new self(
            message: 'No existe un consentimiento activo para revocar en esa política.',
            errorCode: 'CONSENT_NOT_FOUND',
            statusCode: 404,
        );
    }
}

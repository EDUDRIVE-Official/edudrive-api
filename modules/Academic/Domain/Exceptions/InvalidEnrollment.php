<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class InvalidEnrollment extends DomainException
{
    public static function create(): self
    {
        return new self(
            message: 'La matricula no es valida.',
            errorCode: 'INVALID_ENROLLMENT',
            statusCode: 422,
        );
    }
}
